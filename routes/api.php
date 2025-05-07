<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CommuneController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationImageController;
use App\Http\Controllers\LocationStarController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductSizeController;
use App\Http\Controllers\ProductTagController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductColorController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\CustomerController;
use Illuminate\Support\Facades\Route;

// ===============================
// PUBLIC ROUTES - No Authentication Required
// ===============================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-pass', [AuthController::class, 'forgotPass']);
    Route::post('/forgot-pass/verify-otp', [AuthController::class, 'verifyForgotPassOTP']);
    Route::post('/reset-pass', [AuthController::class, 'resetPass']);
});


// ===============================
// ALL OTHER ROUTES Web
// ===============================

Route::prefix('web')->group(function () {
    Route::prefix('customer')->group(function () {
        Route::post('/', [CustomerController::class, 'store']);
    });
});

// ===============================
// ALL OTHER ROUTES - LOGIN REQUIRED
// ===============================
Route::middleware('login')->group(function () {

    Route::prefix('web')->group(function () {
        Route::prefix('customer')->group(function () {
            Route::get('/', [CustomerController::class, 'theirInfo']);
            Route::put('/update', [CustomerController::class, 'update']);
            Route::delete('/delete', [CustomerController::class, 'destroy']);
        });
        Route::prefix('comment')->group(function () {
            Route::post('/', [CommentController::class, 'store']);
            Route::get('/', [CommentController::class, 'getAllComment']);
            Route::get('/{id}', [CommentController::class, 'getOneComment']);
            Route::put('/update/{id}', [CommentController::class, 'update']);
            Route::put('/update/status/{id}', [CommentController::class, 'lockComment']);
            Route::delete('/{id}', [CommentController::class, 'destroy']);
        });
    });
    // ===============================
    // AUTH ROUTES - All Authenticated Users
    // ===============================
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::delete('/logout', [AuthController::class, 'logout']);
    });

    // ===============================
    // PROFILE ROUTES - All Authenticated Users
    // ===============================
    Route::prefix('profile')->group(function () {
        Route::put('/pass', [ProfileController::class, 'updatePass']);
        Route::put('/info', [ProfileController::class, 'updateInfo']);
        Route::delete('/image', [ProfileController::class, 'resetImage']);
    });


    // ===============================
    // ROUTES FOR STAFF, ADMIN, AND SYSTEM_ADMIN (FULL CRUD except users)
    // ===============================
    Route::middleware('admin:staff,admin,system_admin')->group(function () {

        // Route::prefix('customer')->group(function () {
        //     Route::post('/', [CustomerController::class, 'store']);
        //     Route::get('/', [CustomerController::class, 'index']);
        //     Route::put('/{id}', [CustomerController::class, 'update']);
        //     Route::delete('/{id}', [AuthController::class, 'destroy']);
        // });
        // Location Management Routes
        Route::prefix('locations')->group(function () {
            Route::get('/', [LocationController::class, 'index']);
            Route::post('/', [LocationController::class, 'store']);
            Route::get('/{id}', [LocationController::class, 'find']);
            Route::put('/{id}', [LocationController::class, 'update']);
            Route::delete('/{id}', [LocationController::class, 'destroy']);

            // Location images
            Route::get('/get/images/{id}', [LocationImageController::class, 'getImages']);
            Route::post('/images/{id}', [LocationImageController::class, 'storeImage']);
            Route::delete('/images/{imageId}', [LocationImageController::class, 'destroy']);

            // Location reviews
            Route::post('/reviews/{id}', [LocationStarController::class, 'store']);
            Route::put('/reviews/{reviewId}', [LocationStarController::class, 'update']);
            Route::delete('/reviews/{reviewId}', [LocationStarController::class, 'destroy']);
        });

        // Category Management Routes
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::post('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
            Route::delete('/image/{id}', [CategoryController::class, 'destroyImage']);
        });

        // Tag Management Routes
        Route::prefix('tags')->group(function () {
            Route::get('/', [TagController::class, 'index']);
            Route::post('/', [TagController::class, 'store']);
            Route::put('/{id}', [TagController::class, 'update']);
            Route::delete('/{id}', [TagController::class, 'destroy']);
        });

        // Address Hierarchy Management Routes
        Route::get('provinces', [ProvinceController::class, 'index']);
        Route::post('provinces', [ProvinceController::class, 'store']);
        Route::get('provinces/{id}', [ProvinceController::class, 'show']);
        Route::put('provinces/{id}', [ProvinceController::class, 'update']);
        Route::delete('provinces/{id}', [ProvinceController::class, 'destroy']);

        Route::get('districts', [DistrictController::class, 'index']);
        Route::post('districts', [DistrictController::class, 'store']);
        Route::get('districts/{id}', [DistrictController::class, 'show']);
        Route::put('districts/{id}', [DistrictController::class, 'update']);
        Route::delete('districts/{id}', [DistrictController::class, 'destroy']);

        Route::get('communes', [CommuneController::class, 'index']);
        Route::post('communes', [CommuneController::class, 'store']);
        Route::get('communes/{id}', [CommuneController::class, 'show']);
        Route::put('communes/{id}', [CommuneController::class, 'update']);
        Route::delete('communes/{id}', [CommuneController::class, 'destroy']);

        Route::get('villages', [VillageController::class, 'index']);
        Route::post('villages', [VillageController::class, 'store']);
        Route::get('villages/{id}', [VillageController::class, 'show']);
        Route::put('villages/{id}', [VillageController::class, 'update']);
        Route::delete('villages/{id}', [VillageController::class, 'destroy']);

        // Brand Management Routes
        Route::get('brands', [BrandController::class, 'index']);
        Route::post('brands', [BrandController::class, 'store']);
        Route::get('brands/{id}', [BrandController::class, 'show']);
        Route::put('brands/{id}', [BrandController::class, 'update']);
        Route::delete('brands/{id}', [BrandController::class, 'destroy']);

        // Product Management Routes
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/{id}', [ProductController::class, 'find']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
        });

        // Product Relations Management - Changed from apiResource to individual routes
        Route::prefix('product-categories')->group(function () {
            Route::get('/', [ProductCategoryController::class, 'index']);
            Route::post('/', [ProductCategoryController::class, 'store']);
            Route::get('/{id}', [ProductCategoryController::class, 'show']);
            Route::put('/{id}', [ProductCategoryController::class, 'update']);
            Route::delete('/{id}', [ProductCategoryController::class, 'destroy']);
        });

        Route::post('/product-tags', [ProductTagController::class, 'sync']);

        Route::prefix('product-colors')->group(function () {
            Route::get('/', [ProductColorController::class, 'index']);
            Route::post('/', [ProductColorController::class, 'store']);
            Route::get('/{id}', [ProductColorController::class, 'show']);
            Route::put('/{id}', [ProductColorController::class, 'update']);
            Route::delete('/{id}', [ProductColorController::class, 'destroy']);
        });

        Route::prefix('product-sizes')->group(function () {
            Route::get('/', [ProductSizeController::class, 'index']);
            Route::post('/', [ProductSizeController::class, 'store']);
            Route::get('/{id}', [ProductSizeController::class, 'show']);
            Route::put('/{id}', [ProductSizeController::class, 'update']);
            Route::delete('/{id}', [ProductSizeController::class, 'destroy']);
        });

        // Product Images Management
        Route::prefix('product-images')->group(function () {
            Route::post('/', [ProductImageController::class, 'store']);
            Route::get('/get/{id}', [ProductImageController::class, 'getImages']);
            Route::post('/{id}', [ProductImageController::class, 'update']);
            Route::delete('/{id}', [ProductImageController::class, 'destroy']);
        });

        // Product Variants Management
        Route::prefix('product-variants')->group(function () {
            Route::get('/', [ProductVariantController::class, 'index']);
            Route::post('/', [ProductVariantController::class, 'store']);
            Route::get('/{id}', [ProductVariantController::class, 'show']);
            Route::put('/{id}', [ProductVariantController::class, 'update']);
            Route::delete('/{id}', [ProductVariantController::class, 'destory']);
        });
    });

    // ===============================
    // ROUTES FOR ADMIN AND SYSTEM_ADMIN ONLY (USER MANAGEMENT)
    // ===============================
    Route::middleware(['admin:system_admin,admin', 'login'])->group(function () {
        // User Management Routes - ONLY admin and system_admin can access
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::put('/islock/{id}', [UserController::class, 'lockUser']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
        Route::get('/roles', [RoleController::class, 'index']);
    });
});
