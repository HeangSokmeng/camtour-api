<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommuneController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\LocalTransportationController;
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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocationGuideController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\NearbyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductColorController;
use App\Http\Controllers\ProductStarController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TravelActivityController;
use App\Http\Controllers\TravelQuestionController;
use App\Http\Controllers\TravelRecommendationController;
use App\Http\Controllers\Web\AdventureViewController;
use App\Http\Controllers\Web\ChatbotController;
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\HomePageViewController;
use App\Http\Controllers\Web\LocationDetailController;
use App\Http\Controllers\Web\ProductViewController;
use App\Http\Controllers\Web\SiemReapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// ===============================
// PUBLIC ROUTES - No Authentication Required
// ===============================
Route::prefix('web')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-pass', [AuthController::class, 'forgotPass']);
        Route::post('/forgot-pass/verify-otp', [AuthController::class, 'verifyForgotPassOTP']);
        Route::post('/reset-pass', [AuthController::class, 'resetPass']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-pass', [AuthController::class, 'forgotPass']);
    Route::post('/forgot-pass/verify-otp', [AuthController::class, 'verifyForgotPassOTP']);
    Route::post('/reset-pass', [AuthController::class, 'resetPass']);
});


// ===============================
// ALL OTHER ROUTES Web
// ===============================

Route::prefix('web/view')->group(function () {
    Route::get('/', [TravelRecommendationController::class, 'getHotels']);
    Route::get('travel-questions', [TravelRecommendationController::class, 'getQuestions']);
    Route::post('travel-start', [TravelRecommendationController::class, 'start']);
    Route::post('travel-answer', [TravelRecommendationController::class, 'submitAnswer']);
    Route::get('travel-recommendation/{sessionId}', [TravelRecommendationController::class, 'getRecommendation']);
    Route::prefix('travel-activities')->group(function () {
        Route::get('/', [TravelActivityController::class, 'index']);
        Route::post('/', [TravelActivityController::class, 'store']);
        Route::get('/{travelActivity}', [TravelActivityController::class, 'show']);
        Route::put('/{travelActivity}', [TravelActivityController::class, 'update']);
        Route::delete('/{travelActivity}', [TravelActivityController::class, 'destroy']);
        Route::get('/location/{locationId}', [TravelActivityController::class, 'getByLocation']);
    });


    Route::prefix('nearby')->group(function () {
        Route::get('destinations/{destinationId}', [NearbyController::class, 'getNearbyDestinations']);
        Route::get('attractions/{destinationName}', [NearbyController::class, 'getNearbyAttractions']);
        Route::post('recommendations', [NearbyController::class, 'getCustomNearbyRecommendations']);
        Route::get('within-radius', [NearbyController::class, 'getDestinationsWithinRadius']);
    });
    Route::prefix('travel')->group(function () {
        Route::post('start', [TravelRecommendationController::class, 'start']);
        Route::get('questions', [TravelRecommendationController::class, 'getQuestions']);
        Route::get('questions/{type}', [TravelRecommendationController::class, 'getQuestionByType']);
        Route::post('answer', [TravelRecommendationController::class, 'submitAnswer']);
        Route::get('recommendation/{sessionId}', [TravelRecommendationController::class, 'getRecommendation']);
        Route::get('transportation-costs', [TravelRecommendationController::class, 'getTransportationCosts']);
    });

    // Questions Management Routes
    Route::prefix('questions')->group(function () {
        Route::get('/', [QuestionController::class, 'index']);
        Route::post('/', [QuestionController::class, 'store']);
        Route::get('/{id}', [QuestionController::class, 'show']);
        Route::put('/{id}', [QuestionController::class, 'update']);
        Route::delete('/{id}', [QuestionController::class, 'destroy']);

        // Question Options
        Route::post('/{questionId}/options', [QuestionController::class, 'addOption']);
        Route::put('/options/{optionId}', [QuestionController::class, 'updateOption']);
        Route::delete('/options/{optionId}', [QuestionController::class, 'deleteOption']);
    });

    // Destinations Routes
    Route::prefix('destinations')->group(function () {
        Route::get('/', [TravelRecommendationController::class, 'getDestinations']);
        Route::post('/', [DestinationController::class, 'store']);
        Route::get('/{id}', [DestinationController::class, 'show']);
        Route::put('/{id}', [DestinationController::class, 'update']);
        Route::delete('/{id}', [DestinationController::class, 'destroy']);
    });

    Route::prefix('local-transport')->group(function () {
        Route::get('/types', [LocalTransportationController::class, 'getTypes']);
        Route::get('/type/{type}', [LocalTransportationController::class, 'getByType']);
        Route::get('/statistics', [LocalTransportationController::class, 'getStatistics']);
        Route::post('/recommendations', [LocalTransportationController::class, 'getRecommendations']);
        Route::post('/compare', [LocalTransportationController::class, 'compare']);
        Route::post('/search', [LocalTransportationController::class, 'search']);
        // Basic CRUD Operations (/{id} route MUST be last)
        Route::get('/', [LocalTransportationController::class, 'index']);
        Route::post('/', [LocalTransportationController::class, 'store']);
        Route::get('/{id}', [LocalTransportationController::class, 'show']);
        Route::put('/{id}', [LocalTransportationController::class, 'update']);
        Route::delete('/{id}', [LocalTransportationController::class, 'destroy']);
    });

    Route::prefix('meals')->group(function () {
        Route::get('/', [MealController::class, 'index']);
        Route::post('/', [MealController::class, 'store']);
        Route::get('/{id}', [MealController::class, 'show']);
        Route::put('/{id}', [MealController::class, 'update']);
        Route::delete('/{id}', [MealController::class, 'destroy']);
        Route::get('/categories', [MealController::class, 'getMealCategories']);
        Route::get('/category/{category}', [MealController::class, 'getMealsByCategory']);
        Route::get('/cuisine/{cuisine}', [MealController::class, 'getMealsByCuisine']);
        Route::get('/popular', [MealController::class, 'getPopularMeals']);
        Route::post('/recommendations', [MealController::class, 'getMealRecommendations']);
        Route::post('/search', [MealController::class, 'search']);
        Route::post('/dietary', [MealController::class, 'getDietaryRecommendations']);
        Route::post('/calculate-costs', [MealController::class, 'calculateMealCosts']);
        Route::get('/statistics', [MealController::class, 'getStatistics']);
    });
    Route::prefix('customer')->group(function () {
        Route::post('/', [CustomerController::class, 'store']);
    });
    Route::prefix('location')->group(function () {
        Route::get('/provinces', [LocationController::class, 'getProvinces']);
        Route::prefix('guide')->group(function () {
            Route::get('', [LocationDetailController::class, 'locationGuide']);
        });
        Route::get('/detail/{id}', [LocationDetailController::class, 'getOneLocationView']);
        Route::get('/', [HomePageViewController::class, 'getLocationAndProduct']);
        Route::get('/{id}', [HomePageViewController::class, 'find']);
        Route::get('/districts/{provinceId}', [LocationController::class, 'getDistricts']);
        Route::get('/communes/{districtId}', [LocationController::class, 'getCommunes']);
        Route::get('/villages/{communeId}', [LocationController::class, 'getVillages']);
        Route::get('/stats', [AdventureViewController::class, 'getLocationStats']);
    });


    // Optional: Get location statistics
    Route::prefix('adventure')->group(function () {
        Route::get('/detail/{id}', [LocationDetailController::class, 'getOneLocationView']);
        Route::get('/', [AdventureViewController::class, 'getAdventure']);
    });
    Route::prefix('siemreap')->group(function () {
        Route::get('/detail/{id}', [LocationDetailController::class, 'getOneLocationView']);
        Route::get('/', [SiemReapController::class, 'getSiemReapLists']);
    });
    Route::prefix('product')->group(function () {
        Route::get('', [ProductViewController::class, 'index']);
        Route::get('/{id}', [ProductViewController::class, 'find']);
    });
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
    });
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
    });
    Route::post('/chatbot', [ChatbotController::class, 'process']);
    Route::get('/chatbot/status', [ChatbotController::class, 'status']);
});

// ==================================
// ALL OTHER ROUTES - LOGIN REQUIRED
// ==================================
Route::middleware('login')->group(function () {
    Route::prefix('web')->group(function () {
        Route::prefix('wishlist')->group(function () {
            Route::post('', [WishlistController::class, 'store']);
            Route::get('', [WishlistController::class, 'index']);
            Route::delete('/clear', [WishlistController::class, 'clear']);
            Route::delete('/{itemId}', [WishlistController::class, 'destroy']);
            Route::put('/sync', [WishlistController::class, 'sync']);
            Route::get('/count', [WishlistController::class, 'count']);
            Route::get('/type/{type}', [WishlistController::class, 'byType']);
            Route::post('/check', [WishlistController::class, 'check']);
        });
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::delete('/logout', [AuthController::class, 'logout']);
        });
        Route::prefix('customer')->group(function () {
            Route::get('/', [CustomerController::class, 'theirInfo']);
            Route::put('/update', [CustomerController::class, 'update']);
            Route::delete('/delete', [CustomerController::class, 'destroy']);
        });
        Route::prefix('product/order')->group(function () {
            Route::post('/', [OrderController::class, 'createOrder']);
            Route::get('/', [OrderController::class, 'getOrderList']);
            Route::get('/my-order', [OrderController::class, 'getMyOrderList']);
            Route::get('/{id}', [OrderController::class, 'getOrderDetail']);
            Route::put('/', [OrderController::class, 'updateCartItem']);
            Route::put('/update/status/{id}', [OrderController::class, 'updateStatus']);
            // Route::put('/status/{id}', [OrderController::class, 'updateOrderStatus']);
            Route::delete('/{id}', [OrderController::class, 'removeCartItem']);
            Route::delete('/cart/clear', [OrderController::class, 'clearCart']);
        });
        Route::prefix('comment')->group(function () {
            Route::post('/', [CommentController::class, 'store']);
            Route::get('/', [CommentController::class, 'getAll']);
            Route::get('/{id}', [CommentController::class, 'getOne']);
            Route::put('/update/{id}', [CommentController::class, 'update']);
            Route::put('/update/status/{id}', [CommentController::class, 'lockComment']);
            Route::delete('/{id}', [CommentController::class, 'destroy']);
        });
        Route::prefix('locations')->group(function () {
            Route::prefix('reviews')->group(function () {
                Route::post('/{id}', [LocationStarController::class, 'store']);
                Route::put('/{reviewId}', [LocationStarController::class, 'update']);
                Route::delete('/{reviewId}', [LocationStarController::class, 'destroy']);
            });
        });
        Route::prefix('product')->group(function () {
            Route::prefix('reviews')->group(function () {
                Route::post('/{id}', [ProductStarController::class, 'store']);
                Route::get('', [ProductStarController::class, 'index']);
                Route::put('/{reviewId}', [ProductStarController::class, 'update']);
                Route::delete('/{reviewId}', [ProductStarController::class, 'destroy']);
            });
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
        Route::post('/update/{id}', [UserController::class, 'update']);
        Route::put('/pass', [ProfileController::class, 'updatePass']);
        Route::put('/info', [ProfileController::class, 'updateInfo']);
        Route::put('/image', [ProfileController::class, 'resetImage']);
    });



    // ===============================
    // ROUTES FOR STAFF, ADMIN, AND SYSTEM_ADMIN (FULL CRUD except users)
    // ===============================
    Route::middleware('admin:staff,admin,system_admin')->group(function () {
            // Hotels Routes
    Route::prefix('hotels')->group(function () {
        Route::get('/', [TravelRecommendationController::class, 'getHotels']);
        Route::post('/', [HotelController::class, 'store']);
        Route::get('/{id}', [HotelController::class, 'show']);
        Route::put('/{id}', [HotelController::class, 'update']);
        Route::delete('/{id}', [HotelController::class, 'destroy']);
    });
        Route::prefix('travel-activities')->group(function () {
            Route::get('/', [TravelActivityController::class, 'index']);
            Route::post('/', [TravelActivityController::class, 'store']);
            Route::get('/{travelActivity}', [TravelActivityController::class, 'show']);
            Route::put('/{travelActivity}', [TravelActivityController::class, 'update']);
            Route::delete('/{travelActivity}', [TravelActivityController::class, 'destroy']);
            Route::get('/location/{locationId}', [TravelActivityController::class, 'getByLocation']);
        });
        Route::prefix('nearby')->group(function () {
            Route::get('destinations/{destinationId}', [NearbyController::class, 'getNearbyDestinations']);
            Route::get('attractions/{destinationName}', [NearbyController::class, 'getNearbyAttractions']);
            Route::post('recommendations', [NearbyController::class, 'getCustomNearbyRecommendations']);
            Route::get('within-radius', [NearbyController::class, 'getDestinationsWithinRadius']);
        });
        Route::prefix('travel')->group(function () {
            Route::post('start', [TravelRecommendationController::class, 'start']);
            Route::get('questions', [TravelRecommendationController::class, 'getQuestions']);
            Route::get('questions/{type}', [TravelRecommendationController::class, 'getQuestionByType']);
            Route::post('answer', [TravelRecommendationController::class, 'submitAnswer']);
            Route::get('recommendation/{sessionId}', [TravelRecommendationController::class, 'getRecommendation']);
            Route::get('transportation-costs', [TravelRecommendationController::class, 'getTransportationCosts']);
        });
        // Questions Management Routes
        Route::prefix('questions')->group(function () {
            Route::get('/', [QuestionController::class, 'index']);
            Route::post('/', [QuestionController::class, 'store']);
            Route::get('/{id}', [QuestionController::class, 'show']);
            Route::put('/{id}', [QuestionController::class, 'update']);
            Route::delete('/{id}', [QuestionController::class, 'destroy']);
            // Question Options
            Route::post('/{questionId}/options', [QuestionController::class, 'addOption']);
            Route::put('/options/{optionId}', [QuestionController::class, 'updateOption']);
            Route::delete('/options/{optionId}', [QuestionController::class, 'deleteOption']);
        });
        // Destinations Routes
        Route::prefix('destinations')->group(function () {
            Route::get('/', [TravelRecommendationController::class, 'getDestinations']);
            Route::post('/', [DestinationController::class, 'store']);
            Route::get('/{id}', [DestinationController::class, 'show']);
            Route::put('/{id}', [DestinationController::class, 'update']);
            Route::delete('/{id}', [DestinationController::class, 'destroy']);
        });
        // Hotels Routes
        Route::prefix('hotels')->group(function () {
            Route::get('/', [TravelRecommendationController::class, 'getHotels']);
            Route::post('/', [HotelController::class, 'store']);
            Route::get('/{id}', [HotelController::class, 'show']);
            Route::put('/{id}', [HotelController::class, 'update']);
            Route::delete('/{id}', [HotelController::class, 'destroy']);
        });
        Route::prefix('local-transport')->group(function () {
            Route::get('/types', [LocalTransportationController::class, 'getTypes']);
            Route::get('/type/{type}', [LocalTransportationController::class, 'getByType']);
            Route::get('/statistics', [LocalTransportationController::class, 'getStatistics']);
            Route::post('/recommendations', [LocalTransportationController::class, 'getRecommendations']);
            Route::post('/compare', [LocalTransportationController::class, 'compare']);
            Route::post('/search', [LocalTransportationController::class, 'search']);
            // Basic CRUD Operations (/{id} route MUST be last)
            Route::get('/', [LocalTransportationController::class, 'index']);
            Route::post('/', [LocalTransportationController::class, 'store']);
            Route::get('/{id}', [LocalTransportationController::class, 'show']);
            Route::put('/{id}', [LocalTransportationController::class, 'update']);
            Route::delete('/{id}', [LocalTransportationController::class, 'destroy']);
        });
        Route::prefix('meals')->group(function () {
            Route::get('/', [MealController::class, 'index']);
            Route::post('/', [MealController::class, 'store']);
            Route::get('/{id}', [MealController::class, 'show']);
            Route::put('/{id}', [MealController::class, 'update']);
            Route::delete('/{id}', [MealController::class, 'destroy']);
            Route::get('/categories', [MealController::class, 'getMealCategories']);
            Route::get('/category/{category}', [MealController::class, 'getMealsByCategory']);
            Route::get('/cuisine/{cuisine}', [MealController::class, 'getMealsByCuisine']);
            Route::get('/popular', [MealController::class, 'getPopularMeals']);
            Route::post('/recommendations', [MealController::class, 'getMealRecommendations']);
            Route::post('/search', [MealController::class, 'search']);
            Route::post('/dietary', [MealController::class, 'getDietaryRecommendations']);
            Route::post('/calculate-costs', [MealController::class, 'calculateMealCosts']);
            Route::get('/statistics', [MealController::class, 'getStatistics']);
        });


        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'getStats']);
            Route::get('/data', [DashboardController::class, 'getAllDashboardData']);
            Route::get('/activity', [DashboardController::class, 'getRecentActivity']);
            Route::get('/locations/top', [DashboardController::class, 'getTopLocations']);
            Route::get('/locations/by-category', [DashboardController::class, 'getLocationsByCategory']);
            Route::get('/locations/by-province', [DashboardController::class, 'getLocationsByProvince']);
            Route::get('/products/top', [DashboardController::class, 'getTopProducts']);
            Route::get('/products/by-brand', [DashboardController::class, 'getProductsByBrand']);
        });
        Route::prefix('tours')->group(function () {
            Route::get('/', [TravelQuestionController::class, 'index']);
            Route::post('/', [TravelQuestionController::class, 'store']);
            Route::get('/{id}', [TravelQuestionController::class, 'find']);
            Route::put('/{id}', [TravelQuestionController::class, 'update']);
            Route::delete('/{id}', [TravelQuestionController::class, 'destroy']);
            Route::get('/category/{category}', [TravelQuestionController::class, 'getByCategory']);
            Route::get('/location/{location}', [TravelQuestionController::class, 'getByLocation']);
            Route::get('/export/json', [TravelQuestionController::class, 'export']);
            Route::get('/export/data', [TravelQuestionController::class, 'export']);
            Route::get('/export/stats', [TravelQuestionController::class, 'exportStats']);
        });
        Route::prefix('locations')->group(function () {
            Route::prefix('travel-activities')->group(function () {
                Route::get('/', [TravelActivityController::class, 'index']);
                Route::post('/', [TravelActivityController::class, 'store']);
                Route::get('/{travelActivity}', [TravelActivityController::class, 'show']);
                Route::put('/{travelActivity}', [TravelActivityController::class, 'update']);
                Route::delete('/{travelActivity}', [TravelActivityController::class, 'destroy']);
                Route::get('/location/{locationId}', [TravelActivityController::class, 'getByLocation']);
            });
            Route::get('/provinces', [LocationController::class, 'getProvinces']);
            Route::get('/districts/{provinceId}', [LocationController::class, 'getDistricts']);
            Route::get('/communes/{districtId}', [LocationController::class, 'getCommunes']);
            Route::get('/villages/{communeId}', [LocationController::class, 'getVillages']);
            Route::get('/', [LocationController::class, 'index']);
            Route::post('/', [LocationController::class, 'store']);
            Route::get('/{id}', [LocationController::class, 'find']);
            Route::put('/{id}', [LocationController::class, 'update']);
            Route::delete('/{id}', [LocationController::class, 'destroy']);

            Route::get('/get/images/{id}', [LocationImageController::class, 'getImages']);
            Route::post('/images/{id}', [LocationImageController::class, 'storeImage']);
            Route::delete('/images/{imageId}', [LocationImageController::class, 'destroy']);

            // Location reviews
            Route::post('/reviews/{id}', [LocationStarController::class, 'store']);
            Route::put('/reviews/{reviewId}', [LocationStarController::class, 'update']);
            Route::delete('/reviews/{reviewId}', [LocationStarController::class, 'destroy']);

            Route::prefix('guide')->group(function () {
                Route::post('/', [LocationGuideController::class, 'store']);
                Route::get('/get', [LocationGuideController::class, 'index']);
                Route::put('/{id}', [LocationGuideController::class, 'update']);
            });
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

        Route::prefix('invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'index']);
            Route::get('/search-suggestions', [InvoiceController::class, 'searchSuggestions']);
            Route::get('/filter-options', [InvoiceController::class, 'getFilterOptions']);
            Route::get('/export', [InvoiceController::class, 'export']);
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
            Route::put('/update/{id}', [UserController::class, 'update']);
            Route::put('/islock/{id}', [UserController::class, 'lockUser']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
        Route::get('/roles', [RoleController::class, 'roleWeb']);
    });
});
