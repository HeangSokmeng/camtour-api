<?php

namespace App\Http\Controllers\Web;

use ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductViewController extends Controller
{
    public function index(Request $req)
    {
        $req->validate([
            'search' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'category_pro_id' => 'nullable|integer|exists:product_categories,id',
        ]);
        $products = Product::with([
            'brand:id,name,name_km',
            'pcategory:id,name,name_km',
            'tags',
            'stars' => function ($query) {
                $query->select('id', 'product_id', 'star', 'comment', 'rater_id')
                    ->where('status', 1)
                    ->orderBy('id', 'desc');
            },
        ])
            ->where('is_deleted', 0)
            ->where('status', 'published');
        if ($req->filled('search')) {
            $s = $req->input('search');
            $products->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%$s%")
                    ->orWhere('name_km', 'ilike', "%$s%");
            });
        }
        if ($req->filled('brand_id')) {
            $products->where('brand_id', $req->input('brand_id'));
        }
        if ($req->filled('category_id')) {
            $products->where('category_id', $req->input('category_id'));
        }
        if ($req->filled('category_pro_id')) {
            $products->where('product_category_id', $req->input('category_pro_id'));
        }
        $products = $products->orderByDesc('id')->get();
        $products->each(function ($product) {
            $product->stars->each(function ($star) {
                $star->rater_name = optional($star->rater)->first_name . ' ' . optional($star->rater)->last_name;
                unset($star->rater);
            });
        });

        $products->each(function ($product) {
            $product->is_thumbnail = asset("storage/{$product->thumbnail}");
            unset($product->thumbnail);
        });
        return ApiResponse::Pagination($products, $req);
    }

    public function find(Request $req, $id)
    {
        $req->merge(['id' => $id]);
        $req->validate(['id' => 'required|integer|min:1|exists:products,id,is_deleted,0']);
        $product = Product::where('id', $id)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->with([
                'brand:id,name',
                'category:id,name',
                'pcategory:id,name',
                'colors:id,name,product_id',
                'sizes:id,size,product_id',
                'tags',
                'images:id,image,product_id',
                'variants:id,product_id,qty,price',
                'stars' => function ($query) {
                    $query->select('id', 'product_id', 'star', 'comment', 'rater_id')
                        ->where('status', 1)
                        ->orderBy('id', 'desc');
                },
            ])
            ->first();

        if (!$product) return res_fail('Product not found or not published.', [], 1, 404);
        $product->thumbnail = asset("storage/{$product->thumbnail}");
        foreach ($product->images as $pro) {
            $pro->image_url = asset("storage/{$pro->image}");
        }
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_deleted', 0)
            ->where('status', 'published')
            ->with([
                'brand:id,name',
                'pcategory:id,name',
                'colors:id,name,product_id',
                'sizes:id,size,product_id',
                'tags',
                'images:id,image,product_id',
                'variants:id,product_id,qty,price'
            ])
            ->limit(6)
            ->get();

        foreach ($relatedProducts as $repo) {
            $repo->thumbnail = $repo->thumbnail
                ? asset("storage/{$repo->thumbnail}")
                : asset("images/default-thumbnail.png"); // fallback image
        }
        $product->stars->each(function ($star) {
            $star->rater_name = optional($star->rater)->first_name . ' ' . optional($star->rater)->last_name;
            unset($star->rater);
        });
        $product->increment('total_views');
        return res_success("Get detail product success.", [
            'product' => $product,
            'related_products' => $relatedProducts
        ]);
    }
}
