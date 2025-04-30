<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CommuneController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationImageController;
use App\Http\Controllers\LocationStar;
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
use Illuminate\Support\Facades\Route;

// auth api
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('login');
Route::delete('/auth/logout', [AuthController::class, 'logout'])->middleware('login');
Route::post('/auth/forgot-pass', [AuthController::class, 'forgotPass']);
Route::post('/auth/forgot-pass/verify-otp', [AuthController::class, 'verifyForgotPassOTP']);
Route::post('/auth/reset-pass', [AuthController::class, 'resetPass']);

// profile api
Route::put('/profile/pass', [ProfileController::class, 'updatePass'])->middleware('login');
Route::post('/profile/info', [ProfileController::class, 'updateInfo'])->middleware('login');
Route::delete('/profile/image', [ProfileController::class, 'resetImage'])->middleware('login');

// location api
Route::post('/locations', [LocationController::class, 'store'])->middleware('admin');
Route::get('/locations', [LocationController::class, 'index']);
Route::delete('/locations/{id}', [LocationController::class, 'destroy'])->middleware('admin');
Route::get('/locations/{id}', [LocationController::class, 'find']);
Route::put('/locations/{id}', [LocationController::class, 'update'])->middleware('admin');

// location image api
Route::post('/locations/images/{id}', [LocationImageController::class, 'storeImage'])->middleware('admin');
Route::delete('/locations/images/{imageId}', [LocationImageController::class, 'destroy'])->middleware('admin');

// location review api
Route::post('/locations/reviews/{id}', [LocationStarController::class, 'store'])->middleware('login');
Route::delete('/locations/reviews/{reviewId}', [LocationStarController::class, 'destroy'])->middleware('login');
Route::put('/locations/reviews/{reviewId}', [LocationStarController::class, 'update'])->middleware('login');

// setting . category api
Route::post('/categories', [CategoryController::class, 'store'])->middleware('admin');
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories/{id}', [CategoryController::class, 'update'])->middleware('admin');
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('admin');
Route::delete('/categories/image/{id}', [CategoryController::class, 'destroyImage'])->middleware('admin');

// setting . tags api
Route::post('/tags', [TagController::class, 'store'])->middleware('login');
Route::get('/tags', [TagController::class, 'index']);
Route::put('/tags/{id}', [TagController::class, 'update'])->middleware('admin');
Route::delete('/tags/{id}', [TagController::class, 'destroy'])->middleware('admin');

// setting . provinces api
Route::get('/provinces', [ProvinceController::class, 'index']);
Route::post('/provinces', [ProvinceController::class, 'store'])->middleware('admin');
Route::put('/provinces/{id}', [ProvinceController::class, 'update'])->middleware('admin');
Route::delete('/provinces/{id}', [ProvinceController::class, 'destroy'])->middleware('admin');

// setting . district api
Route::get('/districts', [DistrictController::class, 'index']);
Route::post('/districts', [DistrictController::class, 'store'])->middleware('admin');
Route::get('/districts/{id}', [DistrictController::class, 'find']);
Route::put('/districts/{id}', [DistrictController::class, 'update'])->middleware('admin');
Route::delete('/districts/{id}', [DistrictController::class, 'destroy'])->middleware('admin');

// setting . commune api
Route::get('/communes', [CommuneController::class, 'index']);
Route::post('/communes', [CommuneController::class, 'store'])->middleware('admin');
Route::get('/communes/{id}', [CommuneController::class, 'find']);
Route::put('/communes/{id}', [CommuneController::class, 'update'])->middleware('admin');
Route::delete('/communes/{id}', [CommuneController::class, 'destroy'])->middleware('admin');

// setting . village api
Route::get('/villages', [VillageController::class, 'index']);
Route::post('/villages', [VillageController::class, 'store'])->middleware('admin');
Route::get('/villages/{id}', [VillageController::class, 'find']);
Route::put('/villages/{id}', [VillageController::class, 'update'])->middleware('admin');
Route::delete('/villages/{id}', [VillageController::class, 'destroy'])->middleware('admin');

Route::post('/brands', [BrandController::class, 'store'])->middleware('admin');
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{id}', [BrandController::class, 'find']);
Route::put('/brands/{id}', [BrandController::class, 'update'])->middleware('admin');
Route::delete('/brands/{id}', [BrandController::class, 'destroy'])->middleware('admin');

Route::post('/product-categories', [ProductCategoryController::class, 'store'])->middleware('admin');
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::put('/product-categories/{id}', [ProductCategoryController::class, 'update'])->middleware('admin');
Route::delete('/product-categories/{id}', [ProductCategoryController::class, 'destroy'])->middleware('admin');

Route::post('/products', [ProductController::class, 'store'])->middleware('admin');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('admin');
Route::post('/products/{id}', [ProductController::class, 'update'])->middleware('admin');
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'find']);

Route::post('/product-tags', [ProductTagController::class, 'sync'])->middleware('admin');

Route::post('/product-colors', [ProductColorController::class, 'store'])->middleware('admin');
Route::get('/product-colors', [ProductColorController::class, 'index'])->middleware('admin');
Route::put('/product-colors/{id}', [ProductColorController::class, 'update'])->middleware('admin');
Route::delete('/product-colors/{id}', [ProductColorController::class, 'destroy'])->middleware('admin');

Route::post('/product-sizes', [ProductSizeController::class, 'store'])->middleware('admin');
Route::put('/product-sizes/{id}', [ProductSizeController::class, 'update'])->middleware('admin');
Route::delete('/product-sizes/{id}', [ProductSizeController::class, 'destroy'])->middleware('admin');

Route::post('/product-images', [ProductImageController::class, 'store'])->middleware('admin');
Route::post('/product-images/{id}', [ProductImageController::class, 'update'])->middleware('admin');
Route::delete('/product-images/{id}', [ProductImageController::class, 'destroy'])->middleware('admin');

Route::post('/product-variants', [ProductVariantController::class, 'store'])->middleware('admin');
Route::put('/product-variants/{id}', [ProductVariantController::class, 'update'])->middleware('admin');
Route::delete('/product-variants/{id}', [ProductVariantController::class, 'destory'])->middleware('admin');
