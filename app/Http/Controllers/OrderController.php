<?php

namespace App\Http\Controllers;

use ApiResponse;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    private function orderValidation(Request $req)
    {
        return validator($req->all(), [
            'order_no' => 'nullable',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address_to_receive' => 'required|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer,cash_on_delivery',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'nullable|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
        ]);
    }

    public function createOrder(Request $req)
    {
        $validate = $this->orderValidation($req);
        if ($validate->fails()) return ApiResponse::ValidateFail($validate->errors()->first());

        $user = UserService::getAuthUser($req);
        $inputs = $validate->validated();

        $inputs['first_name'] = $inputs['first_name'] ?? $user->first_name;
        $inputs['last_name'] = $inputs['last_name']  ?? $user->last_name;
        $inputs['email'] = $inputs['email']  ?? $user->email;
        $inputs['phone']  = $inputs['phone'] ?? $user->phone;
        $inputs['order_no'] = $this->generateOrderNumber();
        $inputs['create_uid'] = $user->id;
        $inputs['update_uid'] = $user->id;

        // Calculate total and discount
        $total = 0;
        foreach ($inputs['items'] as &$item) {
            $subtotal = $item['price'] * $item['qty'];
            $item['subtotal'] = $subtotal;
            $total += $subtotal;
        }

        $discount = $inputs['discount_amount'] ?? 0;
        $totalAfterDiscount = $total - $discount;
        $inputs['total_amount'] = $totalAfterDiscount;

        try {
            DB::beginTransaction();

            // Create the order
            $order = Order::create($inputs);

            foreach ($inputs['items'] as $item) {
                $orderItem = new OrderDetail();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'];
                $orderItem->qty = $item['qty'];
                $orderItem->price = $item['price'];
                $orderItem->subtotal = $item['subtotal'];

                if (!empty($item['variant_id'])) {
                    $orderItem->variant_id = $item['variant_id'];
                    $checkStock = $this->updateVariantStock($item['variant_id'], $item['product_id'], $item['qty']);
                    if (!$checkStock) {
                        DB::rollBack();
                        return ApiResponse::ValidateFail('Variant stock is not enough.');
                    }
                }

                $orderItem->create_uid = $user->id;
                $orderItem->update_uid = $user->id;
                $orderItem->save();
            }

            DB::commit();
            return ApiResponse::JsonResult(null, 'Created');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::Error('Failed to save: ' . $e->getMessage());
        }
    }
    protected function updateOrderStatus(Request $req, $id)
    {
        $user = UserService::getAuthUser($req);
        $order = Order::where('is_deleted', 0)->with('details')->find($id);
        if (!$order)  return res_fail('Order not found.', [], 1, 404);
        if ($order->status === 'pending') {
            $newStatus = $req->input('status');
            if (!in_array($newStatus, ['completed', 'cancelled'])) {
                return res_fail('Invalid status update.', [], 1, 400);
            }
            if ($newStatus === 'cancelled') {
                foreach ($order->details as $detail) {
                    $this->restoreVariantStock(
                        $detail->variant_id,
                        $detail->product_id,
                        $detail->qty
                    );
                }
            }
            $order->status = $newStatus;
            $order->update_uid = $user->id;
            $order->save();
            return res_success('Order status updated successfully.');
        }
        return res_fail('Only pending orders can be updated.', [], 1, 400);
    }

    private function restoreVariantStock($variantId, $productId, $quantity)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) return false;
        $variant->qty += $quantity;
        $variant->update_uid = Auth::id() ?? 1;
        $variant->save();
        return true;
    }


    public function getOrderList(Request $req)
    {
        $orders = Order::with([
            'user',
            'orderDetails.product:id,name',
            'orderDetails.variant.color:id,name,code',
            'orderDetails.variant.size:id,size'
        ])
            ->orderByDesc('created_at')
            ->get();

        // Group by user_id
        $grouped = $orders->groupBy('create_uid')->map(function ($userOrders) {
            $user = $userOrders->first()->user;

            return [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'orders' => $userOrders->map(function ($order) {
                    return [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                        'payment_method' => $order->payment_method,
                        'discount_amount' => $order->discount_amount,
                        'total_amount' => $order->total_amount,
                        'currency' => $order->currency,
                        'notes' => $order->notes,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'items' => $order->orderDetails->map(function ($item) {
                            return [
                                'product_id' => $item->product_id,
                                'product_name' => $item->product->name ?? null,
                                'qty' => $item->qty,
                                'price' => $item->price,
                                'subtotal' => $item->subtotal,
                                'variant_id' => $item->variant_id,
                                'color_name' => $item->variant->color->name ?? null,
                                'color_code' => $item->variant->color->code ?? null,
                                'size' => $item->variant->size->size ?? null,
                            ];
                        }),
                    ];
                }),
            ];
        })->values(); // remove user_id keys

        return ApiResponse::JsonResult($grouped, 'Orders grouped by user retrieved successfully');
    }
    public function getMyOrderList(Request $req)
    {
        $user = UserService::getAuthUser($req);
        $orders = Order::with([
            'user',
            'orderDetails.product:id,name,thumbnail',
            'orderDetails.variant.color:id,name,code',
            'orderDetails.variant.size:id,size'
        ])
            ->where('create_uid', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Group by user_id
        $grouped = $orders->groupBy('create_uid')->map(function ($userOrders) {
            $user = $userOrders->first()->user;

            return [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'orders' => $userOrders->map(function ($order) {
                    return [
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                        'payment_method' => $order->payment_method,
                        'discount_amount' => $order->discount_amount,
                        'total_amount' => $order->total_amount,
                        'currency' => $order->currency,
                        'notes' => $order->notes,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'items' => $order->orderDetails->map(function ($item) {
                            return [
                                'product_id' => $item->product_id,
                                'product_name' => $item->product->name ?? null,
                                'image' => asset("storage/{$item->product->thumbnail}"),

                                'qty' => $item->qty,
                                'price' => $item->price,
                                'subtotal' => $item->subtotal,
                                'variant_id' => $item->variant_id,
                                'color_name' => $item->variant->color->name ?? null,
                                'color_code' => $item->variant->color->code ?? null,
                                'size' => $item->variant->size->size ?? null,
                            ];
                        }),
                    ];
                }),
            ];
        })->values(); // remove user_id keys

        return ApiResponse::JsonResult($grouped, 'Orders grouped by user retrieved successfully');
    }

    public function getOrderDetail(Request $req, $id)
    {
        $user = UserService::getAuthUser($req);
        $order = Order::with([
            'orderDetails.product:id,name',
            'orderDetails.variant.color:id,name,code',
            'orderDetails.variant.size:id,size'
        ])
            ->where('id', $id)
            ->first();
        if (!$order) {
            return ApiResponse::NotFound('Order not found');
        }
        $formatted = [
            'id' => $order->id,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'email' => $order->email,
            'phone' => $order->phone,
            'order_no' => $order->order_no,
            'payment_method' => $order->payment_method,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'notes' => $order->notes,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'items' => $order->orderDetails->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'variant_id' => $item->variant_id,
                    'color_name' => $item->variant->color->name ?? null,
                    'color_code' => $item->variant->color->code ?? null,
                    'size' => $item->variant->size->size ?? null,
                ];
            }),
        ];

        return ApiResponse::JsonResult($formatted, 'Order detail retrieved successfully');
    }





    private function updateVariantStock($variantId, $productId, $quantity)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) return false;

        $stockQty = $variant->qty ?? 0;
        if ($quantity > $stockQty) return false;

        $variant->qty = max(0, $stockQty - $quantity);
        $variant->update_uid = Auth::id() ?? 1;
        $variant->save();

        return true;
    }

    /**
     * Generate a unique order number
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
        } while (Order::where('order_no', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get all orders for the authenticated user
     */
    public function getUserOrders()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with('items')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Get a specific order by order number
     */
    public function getOrderByNumber($orderNo)
    {
        $order = Order::where('order_no', $orderNo)
            ->with('items')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Check if the user is authorized to view this order
        if (Auth::check() && Auth::id() !== $order->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    /**
     * Update order status (for admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = UserService::getAuthUser($request);
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,cancelled,refunded',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        if ($order->status == 'completed') {
            return ApiResponse::ValidateFail('Order had completed can not update!!');
        }
        $order->status = $request->status;

        if ($request->has('payment_status')) {
            $order->payment_status = $request->payment_status;
        }

        if ($request->has('notes')) {
            $order->notes = $request->notes;
        }
        $order->update_uid = $user->id;
        $order->save();
        return ApiResponse::JsonResult('Update Success');
    }

    /**
     * Update inventory after order is placed
     * Note: This is optional and depends on your inventory management system
     */
    private function updateInventory($items)
    {
        foreach ($items as $item) {
            if (isset($item['variant']) && isset($item['variant']['variantId'])) {
                // If variant exists, update variant stock
                $variant = \App\Models\ProductVariant::find($item['variant']['variantId']);
                if ($variant) {
                    $variant->stock = $variant->stock - $item['quantity'];
                    $variant->save();
                }
            } else {
                // Otherwise update product stock
                $product = \App\Models\Product::find($item['id']);
                if ($product) {
                    $product->stock = $product->stock - $item['quantity'];
                    $product->save();
                }
            }
        }
    }
}
