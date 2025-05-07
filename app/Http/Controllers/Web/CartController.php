<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantAvailabilityService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Http\Resources\Cart\CartResource;

class CartController extends Controller
{
    /**
     * The product variant availability service
     *
     * @var ProductVariantAvailabilityService
     */
    protected $variantAvailabilityService;

    /**
     * Create a new controller instance.
     *
     * @param ProductVariantAvailabilityService $variantAvailabilityService
     * @return void
     */
    public function __construct(ProductVariantAvailabilityService $variantAvailabilityService)
    {
        $this->variantAvailabilityService = $variantAvailabilityService;
    }

    /**
     * Add a product to the cart with variant qty validation
     *
     * @param Request $req
     * @return \Illuminate\Http\Response
     */
    public function addToCart(Request $req)
    {
        // Validate request
        $req->validate([
            'product_id' => 'required|integer|exists:products,id,is_deleted,0,status,published',
            'qty' => 'required|integer|min:1',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'color_id' => 'nullable|integer|exists:product_colors,id,is_deleted,0',
            'size_id' => 'nullable|integer|exists:product_sizes,id,is_deleted,0',
        ]);

        // Get authenticated user
        $user = UserService::getAuthUser($req);

        // Get or create user's cart
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active'],
            ['create_uid' => $user->id, 'update_uid' => $user->id]
        );

        // Check if the product is already in the cart with the same variant/options
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $req->product_id)
            ->where('variant_id', $req->variant_id)
            ->where('color_id', $req->color_id)
            ->where('size_id', $req->size_id)
            ->first();

        // Calculate the total requested qty
        $requestedqty = $req->qty;
        if ($cartItem) {
            $requestedqty += $cartItem->qty;
        }

        // Check product variant availability
        $availability = $this->variantAvailabilityService->checkAvailability(
            $req->product_id,
            $req->variant_id,
            $req->color_id,
            $req->size_id,
            $requestedqty,
            $cartItem ? $cartItem->id : null
        );

        if (!$availability['available']) {
            return res_fail(
                $availability['message'],
                [
                    'available_qty' => $availability['available_qty'],
                    'requested_qty' => $availability['requested_qty']
                ],
                2,
                400
            );
        }

        // Make sure we have the variant_id (might have been resolved by the availability service)
        $variantId = $req->variant_id ?? $availability['variant_id'];

        // Get the product to get its current price
        $product = Product::where('id', $req->product_id)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->first();

        // Get price from the variant if it exists
        $price = $product->price;
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->price > 0) {
                $price = $variant->price;
            }
        }

        // At this point, we know there's enough stock, so proceed
        if ($cartItem) {
            // Update qty if product already exists in cart
            $cartItem->qty = $requestedqty;
            $cartItem->variant_id = $variantId; // Make sure variant_id is set in case it was resolved
            $cartItem->save();
        } else {
            // Add new item to cart
            $cartItem = new CartItem([
                'cart_id' => $cart->id,
                'product_id' => $req->product_id,
                'variant_id' => $variantId,
                'color_id' => $req->color_id,
                'size_id' => $req->size_id,
                'qty' => $req->qty,
                'price' => $price,
            ]);
            $cartItem->save();
        }

        // Update cart totals
        $this->updateCartTotals($cart);

        // Return updated cart
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Product added to cart successfully.', new CartResource($cart));
    }

    /**
     * Update cart item qty with variant availability check
     *
     * @param Request $req
     * @return \Illuminate\Http\Response
     */
    public function updateCartItem(Request $req)
    {
        $req->validate([
            'cart_item_id' => 'required|integer|exists:cart_items,id',
            'qty' => 'required|integer|min:1',
        ]);

        $user = UserService::getAuthUser($req);

        // Find the cart
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return res_fail('Cart not found.', [], 1, 404);
        }

        // Find the cart item
        $cartItem = CartItem::where('id', $req->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return res_fail('Cart item not found.', [], 1, 404);
        }

        // Check product variant availability for the updated qty
        $availability = $this->variantAvailabilityService->checkAvailability(
            $cartItem->product_id,
            $cartItem->variant_id,
            $cartItem->color_id,
            $cartItem->size_id,
            $req->qty,
            $cartItem->id
        );

        if (!$availability['available']) {
            return res_fail(
                $availability['message'],
                [
                    'available_qty' => $availability['available_qty'],
                    'requested_qty' => $availability['requested_qty']
                ],
                2,
                400
            );
        }

        // Update qty
        $cartItem->qty = $req->qty;
        $cartItem->save();

        // Update cart totals
        $this->updateCartTotals($cart);

        // Return updated cart
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Cart updated successfully.', new CartResource($cart));
    }

    /**
     * Remove an item from the cart
     *
     * @param Request $req
     * @return \Illuminate\Http\Response
     */
    public function removeCartItem(Request $req)
    {
        $req->validate([
            'cart_item_id' => 'required|integer|exists:cart_items,id',
        ]);

        $user = UserService::getAuthUser($req);

        // Find the cart
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return res_fail('Cart not found.', [], 1, 404);
        }

        // Find and delete the cart item
        $cartItem = CartItem::where('id', $req->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return res_fail('Cart item not found.', [], 1, 404);
        }

        // Delete cart item
        $cartItem->delete();

        // Update cart totals
        $this->updateCartTotals($cart);

        // Return updated cart
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Item removed from cart successfully.', new CartResource($cart));
    }

    /**
     * Clear the cart
     *
     * @param Request $req
     * @return \Illuminate\Http\Response
     */
    public function clearCart(Request $req)
    {
        $user = UserService::getAuthUser($req);

        // Find the cart
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return res_fail('Cart not found.', [], 1, 404);
        }

        // Delete all cart items
        CartItem::where('cart_id', $cart->id)->delete();

        // Update cart totals
        $cart->total_items = 0;
        $cart->total_amount = 0;
        $cart->save();

        return res_success('Cart cleared successfully.');
    }

    /**
     * Update cart totals based on items
     *
     * @param Cart $cart
     * @return void
     */
    protected function updateCartTotals(Cart $cart)
    {
        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        $totalItems = 0;
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $totalItems += $item->qty;
            $totalAmount += $item->qty * $item->price;
        }

        $cart->total_items = $totalItems;
        $cart->total_amount = $totalAmount;
        $cart->update_uid = $cart->user_id;
        $cart->save();
    }
}
