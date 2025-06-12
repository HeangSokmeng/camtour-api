<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\CartItem;

class ProductVariantAvailabilityService
{
    /**
     * Check if a product variant has enough qty available for the requested amount
     *
     * @param int $productId
     * @param int|null $variantId
     * @param int|null $colorId
     * @param int|null $sizeId
     * @param int $requestedqty
     * @param int|null $existingCartItemId
     * @return array
     */
    public function checkAvailability(int $productId, ?int $variantId, ?int $colorId, ?int $sizeId, int $requestedqty, ?int $existingCartItemId = null)
    {
        // First, check if the product exists and is published
        $product = Product::where('id', $productId)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->first();

        if (!$product) {
            return [
                'available' => false,
                'message' => 'Product not found or not available.',
                'available_qty' => 0,
                'requested_qty' => $requestedqty
            ];
        }
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $productId)
                ->first();
            if (!$variant) {
                return [
                    'available' => false,
                    'message' => 'Product variant not found.',
                    'available_qty' => 0,
                    'requested_qty' => $requestedqty
                ];
            }
            $availableqty = $variant->qty;
        }
        // If no variant_id but color and/or size, find the appropriate variant
        else if ($colorId || $sizeId) {
            $query = ProductVariant::where('product_id', $productId);
            if ($colorId) {
                $query->where('product_color_id', $colorId);
            }
            if ($sizeId) {
                $query->where('product_size_id', $sizeId);
            }
            $variant = $query->first();

            if (!$variant) {
                return [
                    'available' => false,
                    'message' => 'Product variant with the selected options not found.',
                    'available_qty' => 0,
                    'requested_qty' => $requestedqty
                ];
            }
            $availableqty = $variant->qty;
        }
        // If no variant details provided, use the base product's default variant or sum all variants
        else {
            // Get default variant or sum all variants' quantities
            $variants = ProductVariant::where('product_id', $productId)->get();
            if ($variants->isEmpty()) {
                return [
                    'available' => false,
                    'message' => 'No product variants available.',
                    'available_qty' => 0,
                    'requested_qty' => $requestedqty
                ];
            }
            // Find the default variant or just use the first one
            $defaultVariant = $variants->where('is_default', true)->first() ?? $variants->first();
            $availableqty = $defaultVariant->qty;
        }
        // If we're updating an existing cart item, we need to exclude its current qty
        if ($existingCartItemId) {
            $cartItem = CartItem::find($existingCartItemId);
            if ($cartItem) {
                // We're only adding the difference between requested and current qty
                $effectiveRequestedqty = $requestedqty - $cartItem->qty;
                // If we're reducing qty, always allow it
                if ($effectiveRequestedqty <= 0) {
                    return [
                        'available' => true,
                        'message' => 'Product is available.',
                        'available_qty' => $availableqty,
                        'requested_qty' => $requestedqty,
                        'variant_id' => $variantId ?? ($variant->id ?? null)
                    ];
                }
                // Otherwise, check if the additional qty is available
                $requestedqty = $effectiveRequestedqty;
            }
        }

        // Check if there's enough stock
        if ($availableqty < $requestedqty) {
            return [
                'available' => false,
                'message' => "Insufficient stock available. Only {$availableqty} unit(s) available.",
                'available_qty' => $availableqty,
                'requested_qty' => $requestedqty,
                'variant_id' => $variantId ?? ($variant->id ?? null)
            ];
        }

        // Product is available
        return [
            'available' => true,
            'message' => 'Product is available.',
            'available_qty' => $availableqty,
            'requested_qty' => $requestedqty,
            'variant_id' => $variantId ?? ($variant->id ?? null)
        ];
    }

    /**
     * Reserve product variant qty when added to cart
     *
     * @param int $variantId
     * @param int $qty
     * @return bool
     */
    public function reserveqty(int $variantId, int $qty)
    {
        $variant = ProductVariant::where('id', $variantId)
            ->where('qty', '>=', $qty)
            ->first();
        if (!$variant) {
            return false;
        }
        // Decrement the variant qty
        $variant->qty -= $qty;
        $variant->save();
        return true;
    }

    /**
     * Release reserved qty
     *
     * @param int $variantId
     * @param int $qty
     * @return bool
     */
    public function releaseqty(int $variantId, int $qty)
    {
        $variant = ProductVariant::find($variantId);
        if (!$variant)  return false;
        // Increment the variant qty
        $variant->qty += $qty;
        $variant->save();
        return true;
    }
}
