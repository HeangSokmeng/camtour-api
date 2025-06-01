<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Province;
use App\Models\Brand;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary statistics
     */
    public function getStats(Request $request)
    {
        try {
            $timeRange = $request->input('time_range', '30d');
            $dateFrom = $this->getDateFromRange($timeRange);

            $stats = [
                'total_locations' => $this->getTotalLocations(),
                'total_products' => $this->getTotalProducts(),
                'total_users' => $this->getTotalUsers(),
                'total_views' => $this->getTotalViews(),
                'average_location_rating' => $this->getAverageLocationRating(),
                'average_product_rating' => $this->getAverageProductRating(),
                'total_categories' => $this->getTotalCategories(),
                'total_provinces' => $this->getTotalProvinces(),
                'featured_locations' => $this->getFeaturedLocations(),
                'featured_products' => $this->getFeaturedProducts()
            ];

            return $this->successResponse('Dashboard stats retrieved successfully', $stats, $timeRange);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch dashboard stats', $e->getMessage());
        }
    }

    /**
     * Get top performing locations
     */
    public function getTopLocations(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $locations = Location::with(['category', 'province', 'stars'])
                ->where('is_deleted', 0)
                ->select([
                    'id',
                    'name',
                    'name_local',
                    'thumbnail',
                    'category_id',
                    'province_id',
                    'total_view',
                    'min_money',
                    'max_money'
                ])
                ->orderBy('total_view', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($location) {
                    return $this->formatLocationData($location);
                });

            return $this->successResponse('Top locations retrieved successfully', $locations);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch top locations', $e->getMessage());
        }
    }

    /**
     * Get top performing products
     */
    public function getTopProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $products = Product::with(['brand', 'category', 'pcategory', 'stars'])
                ->where('is_deleted', 0)
                ->select([
                    'id',
                    'brand_id',
                    'category_id',
                    'product_category_id',
                    'name',
                    'name_km',
                    'code',
                    'thumbnail',
                    'price',
                    'total_views'
                ])
                ->orderBy('total_views', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($product) {
                    return $this->formatProductData($product);
                });

            return $this->successResponse('Top products retrieved successfully', $products);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch top products', $e->getMessage());
        }
    }

    /**
     * Get locations by category for charts
     */
    public function getLocationsByCategory(Request $request)
    {
        try {
            $categories = Category::where('is_deleted', 0)
                ->withCount(['locations' => function($query) {
                    $query->where('is_deleted', 0);
                }])
                ->get();

            $categoryData = $categories->map(function($category) {
                $locations = Location::where('category_id', $category->id)
                    ->where('is_deleted', 0)
                    ->get();

                return [
                    'category' => $category->name,
                    'location_count' => $category->locations_count ?? 0,
                    'total_views' => $locations->sum('total_view'),
                    'avg_min_price' => round($locations->avg('min_money') ?? 0, 2),
                    'avg_max_price' => round($locations->avg('max_money') ?? 0, 2)
                ];
            })->filter(function($item) {
                return $item['location_count'] > 0;
            })->sortByDesc('location_count')->values();

            return $this->successResponse('Category data retrieved successfully', $categoryData);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch category data', $e->getMessage());
        }
    }

    /**
     * Get locations by province for charts
     */
    public function getLocationsByProvince(Request $request)
    {
        try {
            $provinces = Province::withCount(['locations' => function($query) {
                $query->where('is_deleted', 0);
            }])
            ->get();

            $provinceData = $provinces->map(function($province) {
                $totalViews = Location::where('province_id', $province->id)
                    ->where('is_deleted', 0)
                    ->sum('total_view');

                return [
                    'province' => $province->name,
                    'province_en' => $province->name_en ?? $province->name,
                    'location_count' => $province->locations_count ?? 0,
                    'total_views' => $totalViews
                ];
            })->filter(function($item) {
                return $item['location_count'] > 0;
            })->sortByDesc('location_count')->values();

            return $this->successResponse('Province data retrieved successfully', $provinceData);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch province data', $e->getMessage());
        }
    }

    /**
     * Get products by brand for charts
     */
    public function getProductsByBrand(Request $request)
    {
        try {
            $brands = Brand::where('is_deleted', 0)
                ->withCount(['products' => function($query) {
                    $query->where('is_deleted', 0);
                }])
                ->having('products_count', '>', 0)
                ->orderBy('products_count', 'desc')
                ->get();

            $brandData = $brands->map(function($brand) {
                $products = Product::where('brand_id', $brand->id)
                    ->where('is_deleted', 0)
                    ->get();

                return [
                    'brand' => $brand->name,
                    'product_count' => $brand->products_count,
                    'total_views' => $products->sum('total_views'),
                    'avg_price' => round($products->avg('price') ?? 0, 2)
                ];
            });

            return $this->successResponse('Brand data retrieved successfully', $brandData);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch brand data', $e->getMessage());
        }
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getAllDashboardData(Request $request)
    {
        try {
            $timeRange = $request->input('time_range', '30d');

            $data = [
                'stats' => $this->getStats($request)->getData()->data,
                'top_locations' => $this->getTopLocations($request)->getData()->data,
                'top_products' => $this->getTopProducts($request)->getData()->data,
                'category_data' => $this->getLocationsByCategory($request)->getData()->data,
                'province_data' => $this->getLocationsByProvince($request)->getData()->data,
                'brand_data' => $this->getProductsByBrand($request)->getData()->data
            ];

            return $this->successResponse('Dashboard data retrieved successfully', $data, $timeRange);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch dashboard data', $e->getMessage());
        }
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);
            $days = $request->input('days', 7);
            $dateFrom = Carbon::now()->subDays($days);

            $data = [
                'recent_locations' => $this->getRecentLocations($dateFrom, $limit),
                'recent_products' => $this->getRecentProducts($dateFrom, $limit)
            ];

            return $this->successResponse('Recent activity retrieved successfully', $data);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch recent activity', $e->getMessage());
        }
    }

    // Private helper methods for statistics
    private function getTotalLocations()
    {
        return Location::where('is_deleted', 0)->count();
    }

    private function getTotalProducts()
    {
        return Product::where('is_deleted', 0)->count();
    }

    private function getTotalUsers()
    {
        return User::where('is_deleted', 0)->count();
    }

    private function getTotalViews()
    {
        return Location::where('is_deleted', 0)->sum('total_view') +
               Product::where('is_deleted', 0)->sum('total_views');
    }

    private function getTotalCategories()
    {
        return Category::where('is_deleted', 0)->count();
    }

    private function getTotalProvinces()
    {
        return Province::count();
    }

    private function getFeaturedLocations()
    {
        return Location::where('is_deleted', 0)->count();
    }

    private function getFeaturedProducts()
    {
        return Product::where('is_deleted', 0)->count();
    }

    private function getRecentLocations($dateFrom, $limit)
    {
        return Location::where('is_deleted', 0)
            ->where('created_at', '>=', $dateFrom)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'created_at', 'total_view']);
    }

    private function getRecentProducts($dateFrom, $limit)
    {
        return Product::where('is_deleted', 0)
            ->where('created_at', '>=', $dateFrom)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'created_at', 'total_views']);
    }

    // Data formatting methods
    private function formatLocationData($location)
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'name_local' => $location->name_local,
            'thumbnail' => $location->thumbnail ?: Location::DEFAULT_THUMBNAIL,
            'category' => $location->category?->name ?? 'N/A',
            'province' => $location->province?->name ?? 'N/A',
            'total_views' => $location->total_view ?? 0,
            'min_price' => $location->min_money ?? 0,
            'max_price' => $location->max_money ?? 0,
            'average_rating' => $location->stars->avg('stars') ?? 0,
            'total_ratings' => $location->stars->count()
        ];
    }

    private function formatProductData($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'name_km' => $product->name_km,
            'code' => $product->code,
            'thumbnail' => $product->thumbnail ?: Product::DEFAULT_THUMBNAIL,
            'brand' => $product->brand?->name ?? 'N/A',
            'category' => $product->category?->name ?? 'N/A',
            'product_category' => $product->pcategory?->name ?? 'N/A',
            'price' => $product->price ?? 0,
            'total_views' => $product->total_views ?? 0,
            'average_rating' => $product->stars->avg('stars') ?? 0,
            'total_ratings' => $product->stars->count()
        ];
    }

    // Helper method to get date from time range
    private function getDateFromRange($timeRange)
    {
        return match($timeRange) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30)
        };
    }

    // Rating calculation methods
    private function getAverageLocationRating()
    {
        $locations = Location::where('is_deleted', 0)
            ->whereHas('stars')
            ->with('stars')
            ->get();

        if ($locations->isEmpty()) {
            return 0;
        }

        $totalRating = 0;
        $totalRatings = 0;

        foreach ($locations as $location) {
            foreach ($location->stars as $star) {
                $totalRating += $star->stars;
                $totalRatings++;
            }
        }

        return $totalRatings > 0 ? round($totalRating / $totalRatings, 2) : 0;
    }

    private function getAverageProductRating()
    {
        $products = Product::where('is_deleted', 0)
            ->whereHas('stars')
            ->with('stars')
            ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $totalRating = 0;
        $totalRatings = 0;

        foreach ($products as $product) {
            foreach ($product->stars as $star) {
                $totalRating += $star->stars;
                $totalRatings++;
            }
        }

        return $totalRatings > 0 ? round($totalRating / $totalRatings, 2) : 0;
    }

    // Response helper methods
    private function successResponse($message, $data, $timeRange = null)
    {
        $response = [
            'status' => 'OK',
            'status_code' => 200,
            'error' => false,
            'message' => $message,
            'data' => $data
        ];

        if ($timeRange) {
            $response['time_range'] = $timeRange;
        }

        return response()->json($response);
    }

    private function errorResponse($message, $error)
    {
        return response()->json([
            'status' => 'ERROR',
            'status_code' => 500,
            'error' => true,
            'message' => $message,
            'errors' => [$error]
        ], 500);
    }
}
