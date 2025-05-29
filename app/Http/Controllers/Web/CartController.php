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

    protected $variantAvailabilityService;
    public function __construct(ProductVariantAvailabilityService $variantAvailabilityService)
    {
        $this->variantAvailabilityService = $variantAvailabilityService;
    }
    public function addToCart(Request $req)
    {
        $req->validate([
            'product_id' => 'required|integer|exists:products,id,is_deleted,0,status,published',
            'qty' => 'required|integer|min:1',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'color_id' => 'nullable|integer|exists:product_colors,id,is_deleted,0',
            'size_id' => 'nullable|integer|exists:product_sizes,id,is_deleted,0',
        ]);
        $user = UserService::getAuthUser($req);
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active'],
            ['create_uid' => $user->id, 'update_uid' => $user->id]
        );
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $req->product_id)
            ->where('variant_id', $req->variant_id)
            ->where('color_id', $req->color_id)
            ->where('size_id', $req->size_id)
            ->first();
        $requestedqty = $req->qty;
        if ($cartItem)  $requestedqty += $cartItem->qty;
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
        $variantId = $req->variant_id ?? $availability['variant_id'];
        $product = Product::where('id', $req->product_id)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->first();
        $price = $product->price;
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->price > 0) {
                $price = $variant->price;
            }
        }
        if ($cartItem) {
            $cartItem->qty = $requestedqty;
            $cartItem->variant_id = $variantId;
            $cartItem->save();
        } else {
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
        $this->updateCartTotals($cart);
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Product added to cart successfully.', new CartResource($cart));
    }

    public function updateCartItem(Request $req)
    {
        $req->validate([
            'cart_item_id' => 'required|integer|exists:cart_items,id',
            'qty' => 'required|integer|min:1',
        ]);
        $user = UserService::getAuthUser($req);
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        if (!$cart)  return res_fail('Cart not found.', [], 1, 404);
        $cartItem = CartItem::where('id', $req->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();
        if (!$cartItem) {
            return res_fail('Cart item not found.', [], 1, 404);
        }
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
        $cartItem->qty = $req->qty;
        $cartItem->save();
        $this->updateCartTotals($cart);
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Cart updated successfully.', new CartResource($cart));
    }

    public function removeCartItem(Request $req)
    {
        $req->validate([
            'cart_item_id' => 'required|integer|exists:cart_items,id',
        ]);
        $user = UserService::getAuthUser($req);
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        if (!$cart) {
            return res_fail('Cart not found.', [], 1, 404);
        }
        $cartItem = CartItem::where('id', $req->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();
        if (!$cartItem)  return res_fail('Cart item not found.', [], 1, 404);
        $cartItem->delete();
        $this->updateCartTotals($cart);
        $cart->load('items.product', 'items.variant', 'items.color', 'items.size');
        return res_success('Item removed from cart successfully.', new CartResource($cart));
    }
    public function clearCart(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        if (!$cart)  return res_fail('Cart not found.', [], 1, 404);
        CartItem::where('cart_id', $cart->id)->delete();
        $cart->total_items = 0;
        $cart->total_amount = 0;
        $cart->save();
        return res_success('Cart cleared successfully.');
    }

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
