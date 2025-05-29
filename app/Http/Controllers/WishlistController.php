<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\UserWishlist;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * Get current user's wishlist
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = UserService::getAuthUser($request);
            $wishlists = UserWishlist::forUser($user->id)
                ->orderBy('created_at', 'desc')
                ->get();
            $formattedItems = $wishlists->map(function ($wishlist) {
                return $wishlist->formatted_item;
            });
            return response()->json([
                'result' => true,
                'data' => $formattedItems,
                'message' => 'Wishlist loaded successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading wishlist: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to load wishlist'
            ], 500);
        }
    }

    /**
     * Add item to current user's wishlist
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required|string',
                'item_type' => 'required|in:location,product',
                'item_data' => 'nullable|array'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $user = UserService::getAuthUser($request);
            // Log::info($user->id);
            $itemId = $request->input('item_id');
            $itemType = $request->input('item_type');
            $itemData = $request->input('item_data');
            if (UserWishlist::existsForUser($user->id, $itemId, $itemType)) {
                return response()->json([
                    'result' => false,
                    'message' => 'Item already in wishlist'
                ], 409);
            }

            // Validate that the item exists
            $itemExists = $this->validateItemExists($itemId, $itemType);
            if (!$itemExists) {
                return response()->json([
                    'result' => false,
                    'message' => ucfirst($itemType) . ' not found'
                ], 404);
            }
            // Create wishlist entry
            $wishlist = UserWishlist::create([
                'user_id' => $user->id,
                'item_id' => $itemId,
                'item_type' => $itemType,
                'item_data' => $itemData
            ]);

            return response()->json([
                'result' => true,
                'data' => $wishlist->formatted_item,
                'message' => 'Item added to wishlist successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error adding to wishlist: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'item_id' => $request->input('item_id'),
                'item_type' => $request->input('item_type'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Failed to add item to wishlist'
            ], 500);
        }
    }

    /**
     * Remove item from current user's wishlist
     */
    public function destroy(Request $request, string $itemId): JsonResponse
    {
        try {
            $user = UserService::getAuthUser($request);
            $itemType = $request->query('item_type', 'location');
            $deleted = UserWishlist::where('user_id', $user->id)
                ->where('item_id', $itemId)
                ->where('item_type', $itemType)
                ->delete();
            if ($deleted === 0) {
                return response()->json([
                    'result' => false,
                    'message' => 'Item not found in wishlist'
                ], 404);
            }
            return response()->json([
                'result' => true,
                'message' => 'Item removed from wishlist successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing from wishlist: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'item_id' => $itemId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to remove item from wishlist'
            ], 500);
        }
    }

    /**
     * Sync entire wishlist (useful for merging guest data)
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.id' => 'required|string',
                'items.*.type' => 'required|in:location,product',
                'items.*.originalData' => 'nullable|array'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $user = UserService::getAuthUser($request);
            $items = $request->input('items');
            DB::beginTransaction();
            try {
                // Clear existing wishlist
                UserWishlist::where('user_id', $user->id)->delete();
                // Insert new items
                foreach ($items as $item) {
                    // Validate that the item exists
                    if ($this->validateItemExists($item['id'], $item['type'])) {
                        UserWishlist::create([
                            'user_id' => $user->id,
                            'item_id' => $item['id'],
                            'item_type' => $item['type'],
                            'item_data' => $item['originalData'] ?? $item
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'result' => true,
                    'message' => 'Wishlist synchronized successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error syncing wishlist: ' . $e->getMessage(), [
                // 'user_id' => $user->id,
                'items_count' => count($request->input('items', [])),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to sync wishlist'
            ], 500);
        }
    }

    /**
     * Get current user's wishlist count
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $user = UserService::getAuthUser($request);
            $count = UserWishlist::countForUser($user->id);
            return response()->json([
                'result' => true,
                'data' => ['count' => $count]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting wishlist count: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to get wishlist count'
            ], 500);
        }
    }

    /**
     * Check if items are in current user's wishlist
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_ids' => 'required|array',
                'item_ids.*' => 'string',
                'item_type' => 'nullable|in:location,product'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $user = UserService::getAuthUser($request);
            $itemIds = $request->input('item_ids');
            $itemType = $request->input('item_type', 'location');
            $wishlistItems = UserWishlist::where('user_id', $user->id)
                ->where('item_type', $itemType)
                ->whereIn('item_id', $itemIds)
                ->pluck('item_id')
                ->toArray();
            $result = [];
            foreach ($itemIds as $itemId) {
                $result[$itemId] = in_array($itemId, $wishlistItems);
            }
            return response()->json([
                'result' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking wishlist items: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to check wishlist items'
            ], 500);
        }
    }

    /**
     * Get wishlist items by type
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        try {
            if (!in_array($type, ['location', 'product'])) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid item type'
                ], 400);
            }
            $user = UserService::getAuthUser($request);
            $wishlists = UserWishlist::getByTypeForUser($user->id, $type);
            $formattedItems = $wishlists->map(function ($wishlist) {
                return $wishlist->formatted_item;
            });
            return response()->json([
                'result' => true,
                'data' => $formattedItems,
                'message' => "Wishlist {$type}s loaded successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting wishlist by type: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'result' => false,
                'message' => 'Failed to load wishlist items'
            ], 500);
        }
    }

    /**
     * Clear entire wishlist
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = UserService::getAuthUser($request);
            $deleted = UserWishlist::where('user_id', $user->id)->delete();
            return response()->json([
                'result' => true,
                'data' => ['deleted_count' => $deleted],
                'message' => 'Wishlist cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing wishlist: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'result' => false,
                'message' => 'Failed to clear wishlist'
            ], 500);
        }
    }

    /**
     * Validate that an item exists in the database
     */
    private function validateItemExists(string $itemId, string $itemType): bool
    {
        try {
            if ($itemType === 'location') {
                return Location::where('id', $itemId)->exists();
            } elseif ($itemType === 'product') {
                return Product::where('product_id', $itemId)->exists();
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error validating item existence: ' . $e->getMessage(), [
                'item_id' => $itemId,
                'item_type' => $itemType
            ]);
            return false;
        }
    }
}
