<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Meal::active();
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }
        if ($request->has('cuisine_type')) {
            $query->byCuisine($request->cuisine_type);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_person', '<=', $request->max_price);
        }
        if ($request->has('meal_type')) {
            $query->where('meal_type', $request->meal_type);
        }
        if ($request->has('popular_only') && $request->popular_only) {
            $query->popular();
        }
        $meals = $query->orderBy('category')
                      ->orderBy('price_per_person')
                      ->paginate(15);

        return res_paginate($meals, 'Meals retrieved successfully.', $meals->items());
    }

    public function getMealsByCategory($category): JsonResponse
    {
        $validCategories = ['breakfast', 'lunch', 'dinner', 'snack'];
        if (!in_array($category, $validCategories)) {
            return res_fail('Invalid category. Valid categories are: ' . implode(', ', $validCategories), [], 1, 400);
        }
        $meals = Meal::active()
            ->byCategory($category)
            ->orderBy('price_per_person')
            ->get()
            ->map(function($meal) {
                return [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'price_per_person' => $meal->price_per_person,
                    'cuisine_type' => $meal->cuisine_type,
                    'meal_type' => $meal->meal_type,
                    'dietary_options' => $meal->dietary_options,
                    'preparation_time_minutes' => $meal->preparation_time_minutes,
                    'location_type' => $meal->location_type,
                    'is_popular' => $meal->is_popular
                ];
            });
        if ($meals->isEmpty()) {
            return res_fail('No meals found for this category.', [], 1, 404);
        }
        $data = [
            'category' => $category,
            'total_meals' => $meals->count(),
            'price_range' => [
                'min' => $meals->min('price_per_person'),
                'max' => $meals->max('price_per_person'),
                'average' => round($meals->avg('price_per_person'), 2)
            ],
            'meals' => $meals
        ];
        return res_success('Meals retrieved successfully.', $data);
    }

    public function getMealRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'preference' => 'required|string|in:budget_local,mixed_dining,comfort_dining,premium_dining',
            'party_size' => 'required|integer|min:1|max:20',
            'trip_duration' => 'required|integer|min:1|max:30',
            'dietary_requirements' => 'nullable|array',
            'cuisine_preference' => 'nullable|string|in:khmer,western,asian,mixed,local',
            'exclude_meals' => 'nullable|array',
            'include_snacks' => 'boolean'
        ]);
        $dailyBudget = $this->getDailyBudgetByPreference($request->preference);
        $totalMealBudget = $dailyBudget * $request->party_size * $request->trip_duration;
        $recommendations = $this->generateMealPlan(
            $request->preference,
            $request->party_size,
            $request->trip_duration,
            $request->dietary_requirements ?? [],
            $request->cuisine_preference,
            $request->exclude_meals ?? [],
            $request->include_snacks ?? true
        );
        $data = [
            'preference' => $request->preference,
            'preference_description' => $this->getPreferenceDescription($request->preference),
            'daily_budget_per_person' => $dailyBudget,
            'total_meal_budget' => $totalMealBudget,
            'party_size' => $request->party_size,
            'trip_duration' => $request->trip_duration,
            'meal_plan' => $recommendations['meal_plan'],
            'summary' => $recommendations['summary'],
            'budget_analysis' => [
                'total_estimated_cost' => $recommendations['summary']['total_cost'],
                'budget_remaining' => $totalMealBudget - $recommendations['summary']['total_cost'],
                'budget_utilization_percentage' => round(($recommendations['summary']['total_cost'] / $totalMealBudget) * 100, 1),
                'savings_opportunities' => $this->identifySavingsOpportunities($recommendations['meal_plan'], $request->preference)
            ]
        ];
        return res_success('Meal recommendations generated successfully.', $data);
    }


    public function getMealCategories(): JsonResponse
    {
        $categories = Meal::active()
            ->select('category')
            ->distinct()
            ->get()
            ->pluck('category')
            ->toArray();
        $categoryData = [];
        foreach ($categories as $category) {
            $categoryMeals = Meal::active()->byCategory($category);
            $categoryData[] = [
                'category' => $category,
                'meal_count' => $categoryMeals->count(),
                'price_range' => [
                    'min' => $categoryMeals->min('price_per_person'),
                    'max' => $categoryMeals->max('price_per_person'),
                    'average' => round($categoryMeals->avg('price_per_person'), 2)
                ],
                'popular_meals' => $categoryMeals->where('is_popular', true)->pluck('name')->toArray(),
                'cuisine_types' => $categoryMeals->distinct()->pluck('cuisine_type')->filter()->toArray(),
                'meal_types' => $categoryMeals->distinct()->pluck('meal_type')->filter()->toArray()
            ];
        }
        return res_success('Meal categories retrieved successfully.', $categoryData);
    }

    public function getMealsByCuisine($cuisine): JsonResponse
    {
        $validCuisines = ['khmer', 'western', 'asian', 'mixed', 'local'];
        if (!in_array($cuisine, $validCuisines)) {
            return res_fail('Invalid cuisine type. Valid types are: ' . implode(', ', $validCuisines), [], 1, 400);
        }
        $meals = Meal::active()
            ->byCuisine($cuisine)
            ->orderBy('category')
            ->orderBy('price_per_person')
            ->get();
        if ($meals->isEmpty()) {
            return res_fail('No meals found for this cuisine type.', [], 1, 404);
        }
        $groupedMeals = $meals->groupBy('category')->map(function($categoryMeals) {
            return $categoryMeals->map(function($meal) {
                return [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'price_per_person' => $meal->price_per_person,
                    'meal_type' => $meal->meal_type,
                    'dietary_options' => $meal->dietary_options,
                    'preparation_time_minutes' => $meal->preparation_time_minutes,
                    'location_type' => $meal->location_type,
                    'is_popular' => $meal->is_popular
                ];
            });
        })->toArray();
        $data = [
            'cuisine_type' => $cuisine,
            'total_meals' => $meals->count(),
            'categories' => $groupedMeals,
            'price_statistics' => [
                'min' => $meals->min('price_per_person'),
                'max' => $meals->max('price_per_person'),
                'average' => round($meals->avg('price_per_person'), 2)
            ]
        ];
        return res_success('Meals by cuisine retrieved successfully.', $data);
    }

    public function getPopularMeals(): JsonResponse
    {
        $meals = Meal::active()
            ->popular()
            ->orderBy('category')
            ->orderBy('price_per_person')
            ->get()
            ->map(function($meal) {
                return [
                    'id' => $meal->id,
                    'category' => $meal->category,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'price_per_person' => $meal->price_per_person,
                    'cuisine_type' => $meal->cuisine_type,
                    'meal_type' => $meal->meal_type,
                    'dietary_options' => $meal->dietary_options,
                    'location_type' => $meal->location_type,
                    'preparation_time_minutes' => $meal->preparation_time_minutes
                ];
            });
        if ($meals->isEmpty()) {
            return res_fail('No popular meals found.', [], 1, 404);
        }
        $data = [
            'total_popular_meals' => $meals->count(),
            'meals_by_category' => $meals->groupBy('category'),
            'average_price' => round($meals->avg('price_per_person'), 2),
            'cuisine_distribution' => $meals->groupBy('cuisine_type')->map->count()
        ];
        return res_success('Popular meals retrieved successfully.', $data);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|in:breakfast,lunch,dinner,snack',
            'name' => 'required|string|max:255|unique:meals,name',
            'description' => 'nullable|string|max:1000',
            'price_per_person' => 'required|numeric|min:0|max:1000',
            'cuisine_type' => 'nullable|string|in:khmer,western,asian,mixed,local',
            'meal_type' => 'nullable|string|in:street_food,restaurant,hotel,fast_food,fine_dining',
            'dietary_options' => 'nullable|array',
            'dietary_options.*' => 'string|in:vegetarian,vegan,halal,gluten_free,dairy_free,nut_free',
            'preparation_time_minutes' => 'integer|min:1|max:300',
            'location_type' => 'nullable|string|in:local_market,restaurant,hotel,street_vendor',
            'is_popular' => 'boolean',
            'is_active' => 'boolean'
        ]);
        $meal = Meal::create($request->all());
        return res_success('Meal created successfully.', $meal);
    }

    public function show($id): JsonResponse
    {
        $meal = Meal::find($id);
        if (!$meal) {
            return res_fail('Meal not found.', [], 1, 404);
        }
        $similarMeals = Meal::active()
            ->byCategory($meal->category)
            ->where('id', '!=', $meal->id)
            ->whereBetween('price_per_person', [
                $meal->price_per_person * 0.7,
                $meal->price_per_person * 1.3
            ])
            ->limit(3)
            ->get(['id', 'name', 'price_per_person', 'cuisine_type']);
        $data = [
            'meal' => $meal,
            'cost_for_groups' => [
                '1_person' => $meal->price_per_person,
                '2_people' => $meal->price_per_person * 2,
                '4_people' => $meal->price_per_person * 4,
                '6_people' => $meal->price_per_person * 6
            ],
            'similar_meals' => $similarMeals,
            'dietary_friendly' => !empty($meal->dietary_options),
            'preparation_category' => $this->categorizePrepTime($meal->preparation_time_minutes),
            'price_category' => $this->categorizePriceRange($meal->price_per_person)
        ];
        return res_success('Meal retrieved successfully.', $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $meal = Meal::find($id);
        if (!$meal) {
            return res_fail('Meal not found.', [], 1, 404);
        }
        $request->validate([
            'category' => 'string|in:breakfast,lunch,dinner,snack',
            'name' => 'string|max:255|unique:meals,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'price_per_person' => 'numeric|min:0|max:1000',
            'cuisine_type' => 'nullable|string|in:khmer,western,asian,mixed,local',
            'meal_type' => 'nullable|string|in:street_food,restaurant,hotel,fast_food,fine_dining',
            'dietary_options' => 'nullable|array',
            'dietary_options.*' => 'string|in:vegetarian,vegan,halal,gluten_free,dairy_free,nut_free',
            'preparation_time_minutes' => 'integer|min:1|max:300',
            'location_type' => 'nullable|string|in:local_market,restaurant,hotel,street_vendor',
            'is_popular' => 'boolean',
            'is_active' => 'boolean'
        ]);
        $meal->update($request->all());
        return res_success('Meal updated successfully.', $meal);
    }

    public function destroy($id): JsonResponse
    {
        $meal = Meal::find($id);
        if (!$meal) {
            return res_fail('Meal not found.', [], 1, 404);
        }
        $meal->delete();
        return res_success('Meal deleted successfully.', null);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'category' => 'nullable|string|in:breakfast,lunch,dinner,snack',
            'max_price' => 'nullable|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'cuisine_type' => 'nullable|string|in:khmer,western,asian,mixed,local',
            'meal_type' => 'nullable|string|in:street_food,restaurant,hotel,fast_food,fine_dining',
            'dietary_options' => 'nullable|array',
            'dietary_options.*' => 'string|in:vegetarian,vegan,halal,gluten_free,dairy_free,nut_free',
            'location_type' => 'nullable|string|in:local_market,restaurant,hotel,street_vendor',
            'popular_only' => 'boolean',
            'sort_by' => 'nullable|string|in:price_asc,price_desc,name,popular'
        ]);
        $query = Meal::active()
            ->where(function($q) use ($request) {
                $searchTerm = $request->query;
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('cuisine_type', 'like', '%' . $searchTerm . '%');
            });
        if ($request->category) {
            $query->byCategory($request->category);
        }
        if ($request->min_price) {
            $query->where('price_per_person', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price_per_person', '<=', $request->max_price);
        }
        if ($request->cuisine_type) {
            $query->byCuisine($request->cuisine_type);
        }
        if ($request->meal_type) {
            $query->where('meal_type', $request->meal_type);
        }
        if ($request->location_type) {
            $query->where('location_type', $request->location_type);
        }
        if ($request->popular_only) {
            $query->popular();
        }
        if ($request->dietary_options) {
            foreach ($request->dietary_options as $option) {
                $query->whereJsonContains('dietary_options', $option);
            }
        }
        switch ($request->sort_by) {
            case 'price_asc':
                $query->orderBy('price_per_person', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_person', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'popular':
                $query->orderByDesc('is_popular')->orderBy('price_per_person');
                break;
            default:
                $query->orderBy('price_per_person');
        }
        $meals = $query->paginate(12);
        $searchData = [
            'search_query' => $request->query,
            'filters_applied' => array_filter($request->only([
                'category', 'max_price', 'min_price', 'cuisine_type',
                'meal_type', 'dietary_options', 'location_type', 'popular_only'
            ])),
            'total_results' => $meals->total(),
            'results' => $meals->items(),
            'search_suggestions' => $this->getSearchSuggestions($request->query)
        ];
        return res_paginate($meals, 'Meal search completed successfully.', $searchData);
    }

    public function getStatistics(): JsonResponse
    {
        $totalMeals = Meal::active()->count();
        $categories = Meal::active()->select('category')->distinct()->count();
        $cuisines = Meal::active()->select('cuisine_type')->distinct()->count();
        $statistics = [
            'overview' => [
                'total_meals' => $totalMeals,
                'total_categories' => $categories,
                'total_cuisines' => $cuisines,
                'popular_meals' => Meal::active()->popular()->count()
            ],
            'price_analysis' => [
                'overall' => [
                    'min' => Meal::active()->min('price_per_person'),
                    'max' => Meal::active()->max('price_per_person'),
                    'average' => round(Meal::active()->avg('price_per_person'), 2)
                ],
                'by_category' => Meal::active()
                    ->selectRaw('category, MIN(price_per_person) as min_price, MAX(price_per_person) as max_price, AVG(price_per_person) as avg_price')
                    ->groupBy('category')
                    ->get()
                    ->map(function($item) {
                        return [
                            'category' => $item->category,
                            'min_price' => $item->min_price,
                            'max_price' => $item->max_price,
                            'avg_price' => round($item->avg_price, 2)
                        ];
                    })
            ],
            'distribution' => [
                'by_category' => Meal::active()->selectRaw('category, COUNT(*) as count')->groupBy('category')->pluck('count', 'category'),
                'by_cuisine' => Meal::active()->selectRaw('cuisine_type, COUNT(*) as count')->groupBy('cuisine_type')->pluck('count', 'cuisine_type'),
                'by_meal_type' => Meal::active()->selectRaw('meal_type, COUNT(*) as count')->groupBy('meal_type')->pluck('count', 'meal_type'),
                'by_price_range' => [
                    'budget_under_5' => Meal::active()->where('price_per_person', '<', 5)->count(),
                    'affordable_5_to_15' => Meal::active()->whereBetween('price_per_person', [5, 15])->count(),
                    'premium_over_15' => Meal::active()->where('price_per_person', '>', 15)->count()
                ]
            ]
        ];
        return res_success('Meal statistics retrieved successfully.', $statistics);
    }

    private function getDailyBudgetByPreference($preference): float
    {
        $budgets = [
            'budget_local' => 6.5,      // Average of $5-8
            'mixed_dining' => 15.0,     // Average of $12-18
            'comfort_dining' => 25.0,   // Average of $20-30
            'premium_dining' => 42.5    // Average of $35-50
        ];
        return $budgets[$preference] ?? 15.0;
    }

    private function getPreferenceDescription($preference): string
    {
        $descriptions = [
            'budget_local' => 'Street food and local markets - authentic and affordable',
            'mixed_dining' => 'Mix of local and international cuisine at reasonable prices',
            'comfort_dining' => 'Restaurant dining with good quality and service',
            'premium_dining' => 'Fine dining and high-end restaurant experiences'
        ];
        return $descriptions[$preference] ?? 'Standard dining experience';
    }

    private function generateMealPlan($preference, $partySize, $duration, $dietaryRequirements = [], $cuisinePreference = null, $excludeMeals = [], $includeSnacks = true): array
    {
        $plan = [];
        $dailyBudget = $this->getDailyBudgetByPreference($preference);
        $totalCost = 0;
        $totalMeals = 0;
        $mealVariety = [];
        for ($day = 1; $day <= $duration; $day++) {
            $dayPlan = [
                'day' => $day,
                'date' => now()->addDays($day - 1)->format('Y-m-d'),
                'meals' => [],
                'daily_cost_per_person' => 0,
                'daily_total_cost' => 0
            ];
            $breakfast = $this->selectMealByPreference('breakfast', $preference, $dietaryRequirements, $cuisinePreference, $excludeMeals);
            if ($breakfast) {
                $mealData = $this->formatMealData($breakfast, 'breakfast', '08:00', $partySize);
                $dayPlan['meals'][] = $mealData;
                $dayPlan['daily_cost_per_person'] += $breakfast['price_per_person'];
                $totalMeals++;
                $mealVariety[] = $breakfast['cuisine_type'];
            }
            $lunch = $this->selectMealByPreference('lunch', $preference, $dietaryRequirements, $cuisinePreference, $excludeMeals);
            if ($lunch) {
                $mealData = $this->formatMealData($lunch, 'lunch', '12:30', $partySize);
                $dayPlan['meals'][] = $mealData;
                $dayPlan['daily_cost_per_person'] += $lunch['price_per_person'];
                $totalMeals++;
                $mealVariety[] = $lunch['cuisine_type'];
            }
            $dinner = $this->selectMealByPreference('dinner', $preference, $dietaryRequirements, $cuisinePreference, $excludeMeals);
            if ($dinner) {
                $mealData = $this->formatMealData($dinner, 'dinner', '19:00', $partySize);
                $dayPlan['meals'][] = $mealData;
                $dayPlan['daily_cost_per_person'] += $dinner['price_per_person'];
                $totalMeals++;
                $mealVariety[] = $dinner['cuisine_type'];
            }
            if ($includeSnacks) {
                $remainingBudget = $dailyBudget - $dayPlan['daily_cost_per_person'];
                if ($remainingBudget > 2) {
                    $snack = $this->selectSnackWithinBudget($remainingBudget, $dietaryRequirements, $cuisinePreference, $excludeMeals);
                    if ($snack) {
                        $mealData = $this->formatMealData($snack, 'snack', '15:30', $partySize);
                        $dayPlan['meals'][] = $mealData;
                        $dayPlan['daily_cost_per_person'] += $snack['price_per_person'];
                        $totalMeals++;
                        $mealVariety[] = $snack['cuisine_type'];
                    }
                }
            }
            $dayPlan['daily_total_cost'] = $dayPlan['daily_cost_per_person'] * $partySize;
            $totalCost += $dayPlan['daily_total_cost'];
            $plan[] = $dayPlan;
        }
        return [
            'meal_plan' => $plan,
            'summary' => [
                'total_cost' => $totalCost,
                'average_cost_per_person_per_day' => round($totalCost / ($partySize * $duration), 2),
                'total_meals' => $totalMeals,
                'average_meals_per_day' => round($totalMeals / $duration, 1),
                'budget_utilization' => round(($totalCost / ($this->getDailyBudgetByPreference($preference) * $partySize * $duration)) * 100, 1),
                'cuisine_variety' => array_count_values($mealVariety),
                'dietary_compliance' => !empty($dietaryRequirements) ? $this->checkDietaryCompliance($plan, $dietaryRequirements) : 100,
                'meal_distribution' => $this->analyzeMealDistribution($plan)
            ]
        ];
    }

    private function formatMealData($meal, $type, $time, $partySize): array
    {
        return [
            'meal_type' => $type,
            'time' => $time,
            'name' => $meal['name'],
            'description' => $meal['description'],
            'cost_per_person' => $meal['price_per_person'],
            'total_cost' => $meal['price_per_person'] * $partySize,
            'cuisine_type' => $meal['cuisine_type'],
            'location_type' => $meal['location_type'],
            'preparation_time' => $meal['preparation_time_minutes'] ?? 30,
            'dietary_options' => $meal['dietary_options'] ?? [],
            'meal_id' => $meal['id'] ?? null
        ];
    }

    private function selectMealByPreference($category, $preference, $dietaryRequirements = [], $cuisinePreference = null, $excludeMeals = [])
    {
        $query = Meal::active()->byCategory($category);
        if (!empty($excludeMeals)) {
            $query->whereNotIn('id', $excludeMeals);
        }
        if (!empty($dietaryRequirements)) {
            foreach ($dietaryRequirements as $requirement) {
                $query->whereJsonContains('dietary_options', $requirement);
            }
        }
        if ($cuisinePreference && $cuisinePreference !== 'mixed') {
            $query->byCuisine($cuisinePreference);
        }
        $meal = match($preference) {
            'budget_local' => $query->where('meal_type', 'street_food')
                                   ->where('cuisine_type', 'khmer')
                                   ->orderBy('price_per_person')
                                   ->first(),
            'mixed_dining' => $query->where('price_per_person', '<=', 8)
                                   ->orderBy('price_per_person', 'desc')
                                   ->first(),
            'comfort_dining' => $query->where('meal_type', 'restaurant')
                                     ->where('price_per_person', '<=', 15)
                                     ->orderBy('price_per_person', 'desc')
                                     ->first(),
            'premium_dining' => $query->where('price_per_person', '>=', 15)
                                     ->orderBy('price_per_person', 'desc')
                                     ->first(),
            default => $query->orderBy('price_per_person')->first()
        };
        if (!$meal) return null;
        return [
            'id' => $meal->id,
            'name' => $meal->name,
            'description' => $meal->description,
            'price_per_person' => $meal->price_per_person,
            'cuisine_type' => $meal->cuisine_type,
            'location_type' => $meal->location_type,
            'preparation_time_minutes' => $meal->preparation_time_minutes,
            'dietary_options' => $meal->dietary_options
        ];
    }

    private function selectSnackWithinBudget($budget, $dietaryRequirements = [], $cuisinePreference = null, $excludeMeals = [])
    {
        $query = Meal::active()->byCategory('snack')->where('price_per_person', '<=', $budget);

        if (!empty($excludeMeals)) {
            $query->whereNotIn('id', $excludeMeals);
        }
        if (!empty($dietaryRequirements)) {
            foreach ($dietaryRequirements as $requirement) {
                $query->whereJsonContains('dietary_options', $requirement);
            }
        }
        if ($cuisinePreference && $cuisinePreference !== 'mixed') {
            $query->byCuisine($cuisinePreference);
        }
        $meal = $query->orderBy('price_per_person', 'desc')->first();
        if (!$meal) return null;
        return [
            'id' => $meal->id,
            'name' => $meal->name,
            'description' => $meal->description,
            'price_per_person' => $meal->price_per_person,
            'cuisine_type' => $meal->cuisine_type,
            'location_type' => $meal->location_type,
            'preparation_time_minutes' => $meal->preparation_time_minutes,
            'dietary_options' => $meal->dietary_options
        ];
    }

    private function calculateCuisineVariety($plan): array
    {
        $cuisines = [];
        foreach ($plan as $day) {
            foreach ($day['meals'] as $meal) {
                $cuisine = $meal['cuisine_type'] ?? 'unknown';
                $cuisines[$cuisine] = ($cuisines[$cuisine] ?? 0) + 1;
            }
        }
        return $cuisines;
    }

    private function checkDietaryCompliance($plan, $dietaryRequirements): float
    {
        $totalMeals = 0;
        $compliantMeals = 0;
        foreach ($plan as $day) {
            foreach ($day['meals'] as $meal) {
                $totalMeals++;
                $mealOptions = $meal['dietary_options'] ?? [];
                $isCompliant = true;
                foreach ($dietaryRequirements as $requirement) {
                    if (!in_array($requirement, $mealOptions)) {
                        $isCompliant = false;
                        break;
                    }
                }
                if ($isCompliant) {
                    $compliantMeals++;
                }
            }
        }
        return $totalMeals > 0 ? round(($compliantMeals / $totalMeals) * 100, 1) : 0;
    }

    private function analyzeMealDistribution($plan): array
    {
        $distribution = [
            'breakfast' => 0,
            'lunch' => 0,
            'dinner' => 0,
            'snack' => 0
        ];
        foreach ($plan as $day) {
            foreach ($day['meals'] as $meal) {
                $mealType = $meal['meal_type'];
                if (isset($distribution[$mealType])) {
                    $distribution[$mealType]++;
                }
            }
        }
        return $distribution;
    }

    private function identifySavingsOpportunities($mealPlan, $currentPreference): array
    {
        $opportunities = [];
        $totalCost = 0;
        $mealCount = 0;
        foreach ($mealPlan as $day) {
            foreach ($day['meals'] as $meal) {
                $totalCost += $meal['cost_per_person'];
                $mealCount++;
            }
        }
        $avgCost = $mealCount > 0 ? $totalCost / $mealCount : 0;
        switch ($currentPreference) {
            case 'premium_dining':
                $opportunities[] = [
                    'suggestion' => 'Switch to comfort dining',
                    'potential_savings_percentage' => 40,
                    'description' => 'Reduce costs by choosing mid-range restaurants instead of fine dining'
                ];
                $opportunities[] = [
                    'suggestion' => 'Mix premium with local meals',
                    'potential_savings_percentage' => 25,
                    'description' => 'Have 1-2 premium meals and fill rest with authentic local food'
                ];
                break;
            case 'comfort_dining':
                $opportunities[] = [
                    'suggestion' => 'Include more street food',
                    'potential_savings_percentage' => 30,
                    'description' => 'Replace some restaurant meals with authentic street food experiences'
                ];
                break;
            case 'mixed_dining':
                $opportunities[] = [
                    'suggestion' => 'Focus on local cuisine',
                    'potential_savings_percentage' => 20,
                    'description' => 'Choose more local Khmer dishes over international options'
                ];
                break;
        }
        if ($avgCost > 10) {
            $opportunities[] = [
                'suggestion' => 'Eat lunch at local markets',
                'potential_savings_percentage' => 15,
                'description' => 'Market food stalls offer authentic meals at lower prices'
            ];
        }
        return $opportunities;
    }

    private function categorizePrepTime($minutes): string
    {
        if ($minutes <= 10) return 'Quick';
        if ($minutes <= 30) return 'Standard';
        if ($minutes <= 60) return 'Moderate';
        return 'Slow';
    }

    private function categorizePriceRange($price): string
    {
        if ($price < 3) return 'Budget';
        if ($price < 8) return 'Affordable';
        if ($price < 15) return 'Mid-range';
        if ($price < 25) return 'Premium';
        return 'Luxury';
    }

    private function getSearchSuggestions($query): array
    {
        $suggestions = [];
        $similarMeals = Meal::active()
            ->where('name', 'like', '%' . substr($query, 0, 3) . '%')
            ->limit(5)
            ->pluck('name')
            ->toArray();
        $relatedCuisines = Meal::active()
            ->where('cuisine_type', 'like', '%' . $query . '%')
            ->distinct()
            ->pluck('cuisine_type')
            ->toArray();
        return [
            'similar_meals' => $similarMeals,
            'related_cuisines' => $relatedCuisines,
            'popular_searches' => ['amok fish', 'lok lak', 'street food', 'khmer breakfast', 'coconut water']
        ];
    }

    public function getDietaryRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'dietary_requirements' => 'required|array|min:1',
            'dietary_requirements.*' => 'string|in:vegetarian,vegan,halal,gluten_free,dairy_free,nut_free',
            'budget_range' => 'nullable|string|in:low,medium,high',
            'preferred_categories' => 'nullable|array',
            'preferred_categories.*' => 'string|in:breakfast,lunch,dinner,snack'
        ]);

        $query = Meal::active();
        foreach ($request->dietary_requirements as $requirement) {
            $query->whereJsonContains('dietary_options', $requirement);
        }
        if ($request->budget_range) {
            switch ($request->budget_range) {
                case 'low':
                    $query->where('price_per_person', '<=', 5);
                    break;
                case 'medium':
                    $query->whereBetween('price_per_person', [5, 15]);
                    break;
                case 'high':
                    $query->where('price_per_person', '>', 15);
                    break;
            }
        }
        if ($request->preferred_categories) {
            $query->whereIn('category', $request->preferred_categories);
        }
        $meals = $query->orderBy('category')
                      ->orderBy('price_per_person')
                      ->get();
        $groupedMeals = $meals->groupBy('category');
        $data = [
            'dietary_requirements' => $request->dietary_requirements,
            'total_suitable_meals' => $meals->count(),
            'meals_by_category' => $groupedMeals,
            'price_analysis' => [
                'min' => $meals->min('price_per_person'),
                'max' => $meals->max('price_per_person'),
                'average' => round($meals->avg('price_per_person'), 2)
            ],
            'cuisine_availability' => $meals->groupBy('cuisine_type')->map->count(),
            'dietary_tips' => $this->getDietaryTips($request->dietary_requirements)
        ];
        return res_success('Dietary recommendations retrieved successfully.', $data);
    }

    private function getDietaryTips($requirements): array
    {
        $tips = [];
        foreach ($requirements as $requirement) {
            switch ($requirement) {
                case 'vegetarian':
                    $tips[] = 'Look for "jay" (vegetarian) restaurants in Siem Reap';
                    $tips[] = 'Buddhist temples often serve vegetarian meals';
                    break;
                case 'vegan':
                    $tips[] = 'Check that dishes don\'t contain fish sauce or oyster sauce';
                    $tips[] = 'Fresh fruit and vegetable vendors are everywhere';
                    break;
                case 'halal':
                    $tips[] = 'Look for restaurants with halal certification';
                    $tips[] = 'Cham Muslim communities have halal food options';
                    break;
                case 'gluten_free':
                    $tips[] = 'Rice-based dishes are naturally gluten-free';
                    $tips[] = 'Ask about soy sauce ingredients as they may contain wheat';
                    break;
            }
        }
        return array_unique($tips);
    }

    public function calculateMealCosts(Request $request): JsonResponse
    {
        $request->validate([
            'meal_ids' => 'required|array|min:1',
            'meal_ids.*' => 'integer|exists:meals,id',
            'party_size' => 'required|integer|min:1|max:20',
            'days' => 'required|integer|min:1|max:30',
            'meals_per_day' => 'integer|min:1|max:5'
        ]);
        $meals = Meal::whereIn('id', $request->meal_ids)->get();
        $mealsPerDay = $request->meals_per_day ?? 3;
        $calculations = [
            'selected_meals' => $meals->map(function($meal) use ($request) {
                return [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'category' => $meal->category,
                    'price_per_person' => $meal->price_per_person,
                    'total_cost_for_group' => $meal->price_per_person * $request->party_size
                ];
            }),
            'cost_analysis' => [
                'per_meal_average' => round($meals->avg('price_per_person'), 2),
                'daily_cost_per_person' => round($meals->avg('price_per_person') * $mealsPerDay, 2),
                'daily_cost_total' => round($meals->avg('price_per_person') * $mealsPerDay * $request->party_size, 2),
                'trip_cost_per_person' => round($meals->avg('price_per_person') * $mealsPerDay * $request->days, 2),
                'trip_cost_total' => round($meals->avg('price_per_person') * $mealsPerDay * $request->days * $request->party_size, 2)
            ],
            'breakdown_by_category' => $meals->groupBy('category')->map(function($categoryMeals) use ($request, $mealsPerDay) {
                return [
                    'meal_count' => $categoryMeals->count(),
                    'average_price' => round($categoryMeals->avg('price_per_person'), 2),
                    'total_for_trip' => round($categoryMeals->avg('price_per_person') * $request->days * $request->party_size, 2)
                ];
            }),
            'budget_scenarios' => [
                'budget' => round($meals->avg('price_per_person') * 0.7 * $mealsPerDay * $request->days * $request->party_size, 2),
                'standard' => round($meals->avg('price_per_person') * $mealsPerDay * $request->days * $request->party_size, 2),
                'premium' => round($meals->avg('price_per_person') * 1.5 * $mealsPerDay * $request->days * $request->party_size, 2)
            ]
        ];
        return res_success('Meal cost calculation completed successfully.', $calculations);
    }
}
