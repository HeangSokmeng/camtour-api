<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocalTransportation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocalTransportationController extends Controller
{
    /**
     * Get all local transportation options with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = LocalTransportation::active();

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('max_daily_cost')) {
            $query->where('estimated_daily_cost', '<=', $request->max_daily_cost);
        }

        if ($request->has('min_capacity')) {
            $query->where('capacity_people', '>=', $request->min_capacity);
        }

        if ($request->has('driver_included')) {
            $query->where('driver_included', $request->driver_included);
        }

        if ($request->has('booking_method')) {
            $query->where('booking_method', $request->booking_method);
        }

        $transportations = $query->orderBy('type')
                                ->orderBy('estimated_daily_cost')
                                ->get()
                                ->map(function($transport) {
                                    return [
                                        'id' => $transport->id,
                                        'type' => $transport->type,
                                        'name' => $transport->name,
                                        'description' => $transport->description,
                                        'price_per_hour' => $transport->price_per_hour,
                                        'price_per_day' => $transport->price_per_day,
                                        'price_per_trip' => $transport->price_per_trip,
                                        'estimated_daily_cost' => $transport->estimated_daily_cost,
                                        'capacity_people' => $transport->capacity_people,
                                        'suitable_for' => $transport->suitable_for,
                                        'advantages' => $transport->advantages,
                                        'disadvantages' => $transport->disadvantages,
                                        'booking_method' => $transport->booking_method,
                                        'driver_included' => $transport->driver_included,
                                        'safety_rating' => $this->getSafetyRating($transport),
                                        'comfort_rating' => $this->getComfortRating($transport),
                                        'convenience_rating' => $this->getConvenienceRating($transport)
                                    ];
                                });

        return res_success('Local transportation options retrieved successfully.', $transportations);
    }

    /**
     * Get transportation options by type
     */
    public function getByType($type): JsonResponse
    {
        $validTypes = ['motorbike', 'tuk_tuk', 'tricycle'];

        if (!in_array($type, $validTypes)) {
            return res_fail('Invalid transportation type. Valid types are: ' . implode(', ', $validTypes), [], 1, 400);
        }

        $transportations = LocalTransportation::active()
            ->byType($type)
            ->orderBy('estimated_daily_cost')
            ->get()
            ->map(function($transport) {
                return [
                    'id' => $transport->id,
                    'name' => $transport->name,
                    'description' => $transport->description,
                    'estimated_daily_cost' => $transport->estimated_daily_cost,
                    'capacity_people' => $transport->capacity_people,
                    'advantages' => $transport->advantages,
                    'disadvantages' => $transport->disadvantages,
                    'booking_method' => $transport->booking_method,
                    'driver_included' => $transport->driver_included,
                    'suitable_for' => $transport->suitable_for,
                    'safety_rating' => $this->getSafetyRating($transport),
                    'comfort_rating' => $this->getComfortRating($transport),
                    'value_rating' => $this->calculateValueForMoney($transport, $transport->estimated_daily_cost)
                ];
            });

        if ($transportations->isEmpty()) {
            return res_fail('No transportation options found for this type.', [], 1, 404);
        }

        $data = [
            'type' => $type,
            'total_options' => $transportations->count(),
            'price_range' => [
                'min' => $transportations->min('estimated_daily_cost'),
                'max' => $transportations->max('estimated_daily_cost'),
                'average' => round($transportations->avg('estimated_daily_cost'), 2)
            ],
            'capacity_range' => [
                'min' => $transportations->min('capacity_people'),
                'max' => $transportations->max('capacity_people')
            ],
            'transportations' => $transportations
        ];

        return res_success('Transportation options retrieved successfully.', $data);
    }

    /**
     * Get transportation recommendations based on user preferences
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'party_size' => 'required|integer|min:1|max:20',
            'trip_duration' => 'required|integer|min:1|max:30',
            'budget_per_day' => 'required|numeric|min:0',
            'age_range' => 'nullable|string|in:10-15,15-20,20-25,25-35,35-50,50+',
            'preferences' => 'nullable|array',
            'experience_level' => 'nullable|string|in:beginner,intermediate,experienced',
            'comfort_level' => 'nullable|string|in:budget,standard,premium',
            'priority' => 'nullable|string|in:cost,comfort,speed,authentic_experience'
        ]);

        $transportations = LocalTransportation::active()->get();
        $recommendations = [];

        foreach ($transportations as $transport) {
            // Check capacity requirement
            if ($transport->capacity_people < $request->party_size) {
                continue;
            }

            // Check budget constraint
            $dailyCost = $transport->estimated_daily_cost;
            if ($dailyCost > $request->budget_per_day) {
                continue;
            }

            // Check age suitability
            if ($request->age_range && $transport->suitable_for) {
                $ageGroup = $this->mapAgeRangeToGroup($request->age_range);
                if (!in_array($ageGroup, $transport->suitable_for)) {
                    continue;
                }
            }

            $totalCost = $dailyCost * $request->trip_duration;
            $suitabilityScore = $this->calculateSuitabilityScore($transport, $request);

            $recommendations[] = [
                'id' => $transport->id,
                'type' => $transport->type,
                'name' => $transport->name,
                'description' => $transport->description,
                'daily_cost' => $dailyCost,
                'total_cost' => $totalCost,
                'cost_per_person' => round($totalCost / $request->party_size, 2),
                'capacity' => $transport->capacity_people,
                'advantages' => $transport->advantages,
                'disadvantages' => $transport->disadvantages,
                'booking_method' => $transport->booking_method,
                'driver_included' => $transport->driver_included,
                'suitability_score' => $suitabilityScore,
                'recommendation_reason' => $this->getRecommendationReason($transport, $request, $suitabilityScore),
                'booking_tips' => $this->getBookingTips($transport),
                'safety_rating' => $this->getSafetyRating($transport),
                'comfort_rating' => $this->getComfortRating($transport),
                'convenience_rating' => $this->getConvenienceRating($transport),
                'cultural_authenticity' => $this->getCulturalAuthenticity($transport),
                'environmental_impact' => $this->getEnvironmentalImpact($transport)
            ];
        }

        // Sort by suitability score
        usort($recommendations, function($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        $data = [
            'request_summary' => [
                'party_size' => $request->party_size,
                'trip_duration' => $request->trip_duration,
                'budget_per_day' => $request->budget_per_day,
                'total_budget' => $request->budget_per_day * $request->trip_duration,
                'age_range' => $request->age_range,
                'priority' => $request->priority ?? 'balanced'
            ],
            'recommendation_summary' => [
                'total_options' => count($recommendations),
                'average_daily_cost' => count($recommendations) > 0 ? round(collect($recommendations)->avg('daily_cost'), 2) : 0,
                'budget_utilization' => count($recommendations) > 0 ? round((collect($recommendations)->first()['daily_cost'] / $request->budget_per_day) * 100, 1) : 0
            ],
            'top_recommendation' => $recommendations[0] ?? null,
            'all_recommendations' => $recommendations,
            'budget_analysis' => $this->analyzeBudget($recommendations, $request->budget_per_day, $request->trip_duration),
            'comparison_matrix' => $this->generateComparisonMatrix($recommendations)
        ];

        return res_success('Transportation recommendations generated successfully.', $data);
    }

    /**
     * Compare multiple transportation options
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'transport_ids' => 'required|array|min:2|max:5',
            'transport_ids.*' => 'required|integer|exists:local_transportation,id',
            'party_size' => 'required|integer|min:1',
            'trip_duration' => 'required|integer|min:1'
        ]);

        $transportations = LocalTransportation::active()
            ->whereIn('id', $request->transport_ids)
            ->get();

        if ($transportations->count() !== count($request->transport_ids)) {
            return res_fail('Some transportation options not found.', [], 1, 404);
        }

        $comparison = [];
        foreach ($transportations as $transport) {
            $dailyCost = $transport->estimated_daily_cost;
            $totalCost = $dailyCost * $request->trip_duration;

            $comparison[] = [
                'id' => $transport->id,
                'name' => $transport->name,
                'type' => $transport->type,
                'daily_cost' => $dailyCost,
                'total_cost' => $totalCost,
                'cost_per_person' => round($totalCost / $request->party_size, 2),
                'capacity' => $transport->capacity_people,
                'capacity_utilization' => round(($request->party_size / $transport->capacity_people) * 100, 1),
                'advantages' => $transport->advantages,
                'disadvantages' => $transport->disadvantages,
                'booking_method' => $transport->booking_method,
                'driver_included' => $transport->driver_included,
                'ratings' => [
                    'safety' => $this->getSafetyRating($transport),
                    'comfort' => $this->getComfortRating($transport),
                    'convenience' => $this->getConvenienceRating($transport),
                    'value_for_money' => $this->calculateValueForMoney($transport, $totalCost),
                    'cultural_authenticity' => $this->getCulturalAuthenticity($transport)
                ],
                'overall_score' => $this->calculateOverallScore($transport, $totalCost)
            ];
        }

        // Generate comparison insights
        $cheapest = collect($comparison)->sortBy('total_cost')->first();
        $mostComfortable = collect($comparison)->sortByDesc('ratings.comfort')->first();
        $bestValue = collect($comparison)->sortByDesc('ratings.value_for_money')->first();
        $mostAuthentic = collect($comparison)->sortByDesc('ratings.cultural_authenticity')->first();

        $data = [
            'comparison' => $comparison,
            'insights' => [
                'cheapest' => $cheapest,
                'most_comfortable' => $mostComfortable,
                'best_value' => $bestValue,
                'most_authentic' => $mostAuthentic
            ],
            'summary' => [
                'price_range' => [
                    'min' => collect($comparison)->min('total_cost'),
                    'max' => collect($comparison)->max('total_cost'),
                    'difference' => collect($comparison)->max('total_cost') - collect($comparison)->min('total_cost')
                ],
                'capacity_range' => [
                    'min' => collect($comparison)->min('capacity'),
                    'max' => collect($comparison)->max('capacity')
                ],
                'average_ratings' => [
                    'safety' => round(collect($comparison)->avg('ratings.safety'), 1),
                    'comfort' => round(collect($comparison)->avg('ratings.comfort'), 1),
                    'convenience' => round(collect($comparison)->avg('ratings.convenience'), 1)
                ]
            ],
            'recommendations' => $this->generateComparisonRecommendations($comparison, $request)
        ];

        return res_success('Transportation comparison completed successfully.', $data);
    }

    /**
     * Get transportation types with statistics
     */
    public function getTypes(): JsonResponse
    {
        $types = LocalTransportation::active()
            ->select('type')
            ->distinct()
            ->get()
            ->pluck('type')
            ->toArray();

        $typeData = [];
        foreach ($types as $type) {
            $typeTransports = LocalTransportation::active()->byType($type);
            $typeData[] = [
                'type' => $type,
                'count' => $typeTransports->count(),
                'price_range' => [
                    'min' => $typeTransports->min('estimated_daily_cost'),
                    'max' => $typeTransports->max('estimated_daily_cost'),
                    'average' => round($typeTransports->avg('estimated_daily_cost'), 2)
                ],
                'capacity_range' => [
                    'min' => $typeTransports->min('capacity_people'),
                    'max' => $typeTransports->max('capacity_people')
                ],
                'driver_included_options' => $typeTransports->where('driver_included', true)->count(),
                'self_drive_options' => $typeTransports->where('driver_included', false)->count(),
                'popular_options' => $typeTransports->orderBy('estimated_daily_cost')->limit(2)->pluck('name')->toArray()
            ];
        }

        return res_success('Transportation types retrieved successfully.', $typeData);
    }

    /**
     * Create new transportation option
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:motorbike,tuk_tuk,tricycle',
            'name' => 'required|string|max:255|unique:local_transportation,name',
            'description' => 'nullable|string|max:1000',
            'price_per_hour' => 'nullable|numeric|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_trip' => 'nullable|numeric|min:0',
            'capacity_people' => 'required|integer|min:1|max:20',
            'suitable_for' => 'nullable|array',
            'suitable_for.*' => 'string|in:solo_travelers,couples,families,groups,young_adults,older_travelers,budget_travelers,luxury_travelers',
            'advantages' => 'nullable|array',
            'advantages.*' => 'string|max:200',
            'disadvantages' => 'nullable|array',
            'disadvantages.*' => 'string|max:200',
            'booking_method' => 'nullable|string|in:street,hotel,app,rental_shop,online',
            'driver_included' => 'boolean',
            'is_active' => 'boolean'
        ]);

        // Validate that at least one price field is provided
        if (!$request->price_per_hour && !$request->price_per_day && !$request->price_per_trip) {
            return res_fail('At least one price field (per hour, per day, or per trip) must be provided.', [], 422, 422);
        }

        $transportation = LocalTransportation::create($request->all());

        $data = [
            'transportation' => $transportation,
            'estimated_daily_cost' => $transportation->estimated_daily_cost,
            'ratings' => [
                'safety' => $this->getSafetyRating($transportation),
                'comfort' => $this->getComfortRating($transportation),
                'convenience' => $this->getConvenienceRating($transportation)
            ]
        ];

        return res_success('Transportation option created successfully.', $data);
    }

    /**
     * Get specific transportation option with detailed information
     */
    public function show($id): JsonResponse
    {
        $transportation = LocalTransportation::find($id);

        if (!$transportation) {
            return res_fail('Transportation option not found.', [], 1, 404);
        }

        // Get similar transportation options
        $similarOptions = LocalTransportation::active()
            ->where('type', $transportation->type)
            ->where('id', '!=', $transportation->id)
            ->whereBetween('estimated_daily_cost', [
                $transportation->estimated_daily_cost * 0.7,
                $transportation->estimated_daily_cost * 1.3
            ])
            ->limit(3)
            ->get(['id', 'name', 'estimated_daily_cost', 'capacity_people']);

        $data = [
            'transportation' => $transportation,
            'pricing' => [
                'estimated_daily_cost' => $transportation->estimated_daily_cost,
                'cost_for_groups' => [
                    '1_person' => $transportation->estimated_daily_cost,
                    '2_people' => $transportation->estimated_daily_cost,
                    '4_people' => $transportation->estimated_daily_cost,
                    '6_people' => $transportation->capacity_people >= 6 ? $transportation->estimated_daily_cost : 'Not available'
                ],
                'weekly_cost' => $transportation->estimated_daily_cost * 7,
                'monthly_cost' => $transportation->estimated_daily_cost * 30
            ],
            'ratings' => [
                'safety' => $this->getSafetyRating($transportation),
                'comfort' => $this->getComfortRating($transportation),
                'convenience' => $this->getConvenienceRating($transportation),
                'value_for_money' => $this->calculateValueForMoney($transportation, $transportation->estimated_daily_cost),
                'cultural_authenticity' => $this->getCulturalAuthenticity($transportation),
                'environmental_impact' => $this->getEnvironmentalImpact($transportation)
            ],
            'booking_info' => [
                'booking_tips' => $this->getBookingTips($transportation),
                'best_time_to_book' => $this->getBestBookingTime($transportation),
                'negotiation_tips' => $this->getNegotiationTips($transportation)
            ],
            'similar_options' => $similarOptions,
            'suitability' => $this->getSuitabilityInsights($transportation)
        ];

        return res_success('Transportation option retrieved successfully.', $data);
    }

    /**
     * Update transportation option
     */
    public function update(Request $request, $id): JsonResponse
    {
        $transportation = LocalTransportation::find($id);

        if (!$transportation) {
            return res_fail('Transportation option not found.', [], 1, 404);
        }

        $request->validate([
            'type' => 'string|in:motorbike,tuk_tuk,tricycle',
            'name' => 'string|max:255|unique:local_transportation,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'price_per_hour' => 'nullable|numeric|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_trip' => 'nullable|numeric|min:0',
            'capacity_people' => 'integer|min:1|max:20',
            'suitable_for' => 'nullable|array',
            'advantages' => 'nullable|array',
            'disadvantages' => 'nullable|array',
            'booking_method' => 'nullable|string|in:street,hotel,app,rental_shop,online',
            'driver_included' => 'boolean',
            'is_active' => 'boolean'
        ]);

        $transportation->update($request->all());

        $data = [
            'transportation' => $transportation->fresh(),
            'updated_fields' => array_keys($request->all()),
            'new_estimated_daily_cost' => $transportation->fresh()->estimated_daily_cost
        ];

        return res_success('Transportation option updated successfully.', $data);
    }

    /**
     * Delete transportation option
     */
    public function destroy($id): JsonResponse
    {
        $transportation = LocalTransportation::find($id);

        if (!$transportation) {
            return res_fail('Transportation option not found.', [], 1, 404);
        }

        $transportationName = $transportation->name;
        $transportation->delete();

        return res_success("Transportation option '{$transportationName}' deleted successfully.", null);
    }

    /**
     * Search transportation options
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'nullable|string|in:motorbike,tuk_tuk,tricycle',
            'max_daily_cost' => 'nullable|numeric|min:0',
            'min_capacity' => 'nullable|integer|min:1',
            'driver_included' => 'nullable|boolean',
            'booking_method' => 'nullable|string',
            'suitable_for' => 'nullable|array'
        ]);

        $query = LocalTransportation::active()
            ->where(function($q) use ($request) {
                $searchTerm = $request->query;
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('type', 'like', '%' . $searchTerm . '%');
            });

        // Apply filters
        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->max_daily_cost) {
            $query->where('estimated_daily_cost', '<=', $request->max_daily_cost);
        }

        if ($request->min_capacity) {
            $query->where('capacity_people', '>=', $request->min_capacity);
        }

        if ($request->has('driver_included')) {
            $query->where('driver_included', $request->driver_included);
        }

        if ($request->booking_method) {
            $query->where('booking_method', $request->booking_method);
        }

        if ($request->suitable_for) {
            foreach ($request->suitable_for as $suitability) {
                $query->whereJsonContains('suitable_for', $suitability);
            }
        }

        $transportations = $query->orderBy('estimated_daily_cost')->paginate(10);

        $searchData = [
            'search_query' => $request->query,
            'filters_applied' => array_filter($request->only([
                'type', 'max_daily_cost', 'min_capacity', 'driver_included', 'booking_method', 'suitable_for'
            ])),
            'total_results' => $transportations->total(),
            'results' => $transportations->items(),
            'search_suggestions' => $this->getSearchSuggestions($request->query)
        ];

        return res_paginate($transportations, 'Transportation search completed successfully.', $searchData);
    }

    /**
     * Get transportation statistics
     */
    public function getStatistics(): JsonResponse
    {
        $totalTransports = LocalTransportation::active()->count();
        $types = LocalTransportation::active()->distinct('type')->count('type');

        $statistics = [
            'overview' => [
                'total_options' => $totalTransports,
                'total_types' => $types,
                'driver_included_options' => LocalTransportation::active()->where('driver_included', true)->count(),
                'self_drive_options' => LocalTransportation::active()->where('driver_included', false)->count()
            ],
            'cost_analysis' => [
                'overall' => [
                    'min' => LocalTransportation::active()->min('estimated_daily_cost'),
                    'max' => LocalTransportation::active()->max('estimated_daily_cost'),
                    'average' => round(LocalTransportation::active()->avg('estimated_daily_cost'), 2)
                ],
                'by_type' => LocalTransportation::active()
                    ->selectRaw('type, MIN(estimated_daily_cost) as min_cost, MAX(estimated_daily_cost) as max_cost, AVG(estimated_daily_cost) as avg_cost')
                    ->groupBy('type')
                    ->get()
                    ->map(function($item) {
                        return [
                            'type' => $item->type,
                            'min_cost' => $item->min_cost,
                            'max_cost' => $item->max_cost,
                            'avg_cost' => round($item->avg_cost, 2)
                        ];
                    })
            ],
            'capacity_analysis' => [
                'by_type' => LocalTransportation::active()
                    ->selectRaw('type, MIN(capacity_people) as min_capacity, MAX(capacity_people) as max_capacity, AVG(capacity_people) as avg_capacity')
                    ->groupBy('type')
                    ->get()
                    ->map(function($item) {
                        return [
                            'type' => $item->type,
                            'min_capacity' => $item->min_capacity,
                            'max_capacity' => $item->max_capacity,
                            'avg_capacity' => round($item->avg_capacity, 1)
                        ];
                    })
            ],
            'distribution' => [
                'by_type' => LocalTransportation::active()->selectRaw('type, COUNT(*) as count')->groupBy('type')->pluck('count', 'type'),
                'by_booking_method' => LocalTransportation::active()->selectRaw('booking_method, COUNT(*) as count')->groupBy('booking_method')->pluck('count', 'booking_method'),
                'by_driver_included' => [
                    'with_driver' => LocalTransportation::active()->where('driver_included', true)->count(),
                    'self_drive' => LocalTransportation::active()->where('driver_included', false)->count()
                ]
            ]
        ];

        return res_success('Transportation statistics retrieved successfully.', $statistics);
    }

    // Private helper methods

    private function mapAgeRangeToGroup($ageRange): string
    {
        $mappings = [
            '10-15' => 'young_adults',
            '15-20' => 'young_adults',
            '20-25' => 'young_adults',
            '25-35' => 'adults',
            '35-50' => 'families',
            '50+' => 'older_travelers'
        ];

        return $mappings[$ageRange] ?? 'adults';
    }

    private function calculateSuitabilityScore($transport, $request): float
    {
        $score = 0;

        // Budget efficiency (35% weight)
        if ($transport->estimated_daily_cost > 0) {
            $budgetRatio = $request->budget_per_day / $transport->estimated_daily_cost;
            $score += min($budgetRatio, 2) * 35;
        }

        // Capacity match (25% weight)
        if ($transport->capacity_people >= $request->party_size) {
            $capacityRatio = $request->party_size / $transport->capacity_people;
            $score += $capacityRatio * 25;
        }

        // Age suitability (20% weight)
        if ($request->age_range && $transport->suitable_for) {
            $ageGroup = $this->mapAgeRangeToGroup($request->age_range);
            if (in_array($ageGroup, $transport->suitable_for)) {
                $score += 20;
            }
        } else {
            $score += 10; // Neutral if no age preference
        }

        // Priority matching (20% weight)
        $priorityScore = $this->calculatePriorityScore($transport, $request->priority ?? 'balanced');
        $score += $priorityScore * 20;

        return min($score, 100);
    }

    private function calculatePriorityScore($transport, $priority): float
    {
        switch ($priority) {
            case 'cost':
                return $transport->estimated_daily_cost <= 50 ? 1.0 : 0.5;
            case 'comfort':
                return $this->getComfortRating($transport) / 5.0;
            case 'speed':
                return in_array($transport->type, ['motorbike']) ? 1.0 : 0.7;
            case 'authentic_experience':
                return $this->getCulturalAuthenticity($transport) / 5.0;
            default:
                return 0.8; // Balanced
        }
    }

    private function getRecommendationReason($transport, $request, $score): string
    {
        if ($score >= 80) {
            return "Excellent match for your group size and budget with high comfort level.";
        } elseif ($score >= 60) {
            return "Good option that balances cost, capacity, and convenience for your needs.";
        } elseif ($score >= 40) {
            return "Decent choice that meets basic requirements but may have some limitations.";
        } else {
            return "Available option but consider alternatives for better value or comfort.";
        }
    }

    private function getBookingTips($transport): array
    {
        $tips = [];

        switch ($transport->booking_method) {
            case 'street':
                $tips[] = "Available everywhere - just flag one down on the street";
                $tips[] = "Negotiate price before starting your journey";
                $tips[] = "Have small bills ready for payment";
                $tips[] = "Peak hours (7-9am, 5-7pm) may have higher demand";
                break;
            case 'hotel':
                $tips[] = "Ask your hotel to arrange - they often have trusted drivers";
                $tips[] = "Prices may be slightly higher but more reliable";
                $tips[] = "Book in advance for popular destinations";
                $tips[] = "Hotel can provide contact details for return trips";
                break;
            case 'app':
                $tips[] = "Download PassApp or Grab for easy booking";
                $tips[] = "Fixed prices, no negotiation needed";
                $tips[] = "GPS tracking for safety";
                $tips[] = "Digital payment options available";
                break;
            case 'rental_shop':
                $tips[] = "Bring valid driving license and passport";
                $tips[] = "Check vehicle condition before renting";
                $tips[] = "Understand fuel policy and return requirements";
                $tips[] = "Ask for emergency contact numbers";
                break;
            case 'online':
                $tips[] = "Book 24-48 hours in advance for better rates";
                $tips[] = "Read cancellation policy carefully";
                $tips[] = "Save confirmation details on your phone";
                $tips[] = "Contact provider if driver doesn't arrive on time";
                break;
            default:
                $tips[] = "Ask locals for recommendations";
                $tips[] = "Compare prices from multiple providers";
                $tips[] = "Ensure driver understands your destination";
        }

        return $tips;
    }

    private function getSafetyRating($transport): float
    {
        $baseScore = 3.0;

        // Type-based safety
        switch ($transport->type) {
            case 'motorbike':
                $baseScore = $transport->driver_included ? 2.5 : 2.0;
                break;
            case 'tuk_tuk':
                $baseScore = 4.0;
                break;
            case 'tricycle':
                $baseScore = 3.5;
                break;
        }

        // Driver included adds safety
        if ($transport->driver_included) {
            $baseScore += 0.5;
        }

        // Capacity affects stability
        if ($transport->capacity_people >= 4) {
            $baseScore += 0.3;
        }

        return min($baseScore, 5.0);
    }

    private function getComfortRating($transport): float
    {
        $baseScore = 3.0;

        // Type-based comfort
        switch ($transport->type) {
            case 'motorbike':
                $baseScore = 2.0;
                break;
            case 'tuk_tuk':
                $baseScore = strpos(strtolower($transport->name), 'premium') !== false ? 4.5 : 3.5;
                break;
            case 'tricycle':
                $baseScore = strpos(strtolower($transport->name), 'electric') !== false ? 3.5 : 2.5;
                break;
        }

        // Higher capacity usually means more comfort
        if ($transport->capacity_people >= 4) {
            $baseScore += 0.5;
        }

        // Premium options get bonus
        if (strpos(strtolower($transport->name), 'premium') !== false) {
            $baseScore += 1.0;
        }

        return min($baseScore, 5.0);
    }

    private function getConvenienceRating($transport): float
    {
        $baseScore = 3.0;

        // Booking method affects convenience
        switch ($transport->booking_method) {
            case 'app':
                $baseScore = 4.5;
                break;
            case 'hotel':
                $baseScore = 4.0;
                break;
            case 'street':
                $baseScore = 3.5;
                break;
            case 'rental_shop':
                $baseScore = 3.0;
                break;
            case 'online':
                $baseScore = 3.8;
                break;
        }

        // Driver included is more convenient
        if ($transport->driver_included) {
            $baseScore += 0.5;
        }

        // Type convenience
        if ($transport->type === 'tuk_tuk') {
            $baseScore += 0.3; // Good for luggage
        }

        return min($baseScore, 5.0);
    }

    private function calculateValueForMoney($transport, $totalCost): float
    {
        $baseValue = 3.0;

        // Lower cost gets higher value score
        if ($totalCost <= 20) {
            $baseValue = 4.5;
        } elseif ($totalCost <= 50) {
            $baseValue = 4.0;
        } elseif ($totalCost <= 100) {
            $baseValue = 3.5;
        } else {
            $baseValue = 2.5;
        }

        // Adjust based on features
        $comfortRating = $this->getComfortRating($transport);
        $safetyRating = $this->getSafetyRating($transport);

        // Good features justify higher cost
        $featureScore = ($comfortRating + $safetyRating) / 2;
        if ($featureScore >= 4.0) {
            $baseValue += 0.5;
        }

        return min($baseValue, 5.0);
    }

    private function getCulturalAuthenticity($transport): float
    {
        $authenticityScore = 3.0;

        switch ($transport->type) {
            case 'tricycle':
                $authenticityScore = strpos(strtolower($transport->name), 'bicycle') !== false ? 5.0 : 4.0;
                break;
            case 'tuk_tuk':
                $authenticityScore = 4.5;
                break;
            case 'motorbike':
                $authenticityScore = $transport->driver_included ? 4.0 : 3.0;
                break;
        }

        // Street booking is more authentic
        if ($transport->booking_method === 'street') {
            $authenticityScore += 0.5;
        }

        return min($authenticityScore, 5.0);
    }

    private function getEnvironmentalImpact($transport): float
    {
        $ecoScore = 3.0;

        switch ($transport->type) {
            case 'tricycle':
                $ecoScore = strpos(strtolower($transport->name), 'electric') !== false ? 5.0 : 4.5;
                break;
            case 'motorbike':
                $ecoScore = 2.5;
                break;
            case 'tuk_tuk':
                $ecoScore = 2.0;
                break;
        }

        return $ecoScore;
    }

    private function calculateOverallScore($transport, $totalCost): float
    {
        $safety = $this->getSafetyRating($transport);
        $comfort = $this->getComfortRating($transport);
        $convenience = $this->getConvenienceRating($transport);
        $value = $this->calculateValueForMoney($transport, $totalCost);

        return round(($safety + $comfort + $convenience + $value) / 4, 1);
    }

    private function analyzeBudget($recommendations, $budgetPerDay, $tripDuration): array
    {
        if (empty($recommendations)) {
            return [
                'status' => 'no_options',
                'message' => 'No transportation options fit within your budget'
            ];
        }

        $cheapest = collect($recommendations)->min('daily_cost');
        $mostExpensive = collect($recommendations)->max('daily_cost');
        $average = collect($recommendations)->avg('daily_cost');

        $analysis = [
            'budget_status' => $cheapest <= $budgetPerDay ? 'sufficient' : 'insufficient',
            'cheapest_option' => $cheapest,
            'most_expensive_option' => $mostExpensive,
            'average_cost' => round($average, 2),
            'budget_utilization' => round(($cheapest / $budgetPerDay) * 100, 1),
            'savings_potential' => max(0, $budgetPerDay - $cheapest),
            'total_trip_savings' => max(0, ($budgetPerDay - $cheapest) * $tripDuration)
        ];

        if ($cheapest > $budgetPerDay) {
            $analysis['budget_shortfall'] = $cheapest - $budgetPerDay;
            $analysis['suggestions'] = [
                'Consider reducing trip duration',
                'Look for shared transportation options',
                'Mix different transport types for different days'
            ];
        } else {
            $analysis['upgrade_options'] = collect($recommendations)
                ->where('daily_cost', '<=', $budgetPerDay)
                ->sortByDesc('daily_cost')
                ->take(3)
                ->values()
                ->toArray();
        }

        return $analysis;
    }

    private function generateComparisonMatrix($recommendations): array
    {
        if (count($recommendations) < 2) {
            return [];
        }

        $matrix = [];
        $criteria = ['safety_rating', 'comfort_rating', 'convenience_rating', 'daily_cost'];

        foreach ($criteria as $criterion) {
            $values = collect($recommendations)->pluck($criterion)->toArray();

            $matrix[$criterion] = [
                'best' => $criterion === 'daily_cost' ? min($values) : max($values),
                'worst' => $criterion === 'daily_cost' ? max($values) : min($values),
                'average' => round(array_sum($values) / count($values), 2),
                'range' => max($values) - min($values)
            ];
        }

        return $matrix;
    }

    private function generateComparisonRecommendations($comparison, $request): array
    {
        $recommendations = [];

        // Budget recommendation
        $cheapest = collect($comparison)->sortBy('total_cost')->first();
        $recommendations[] = [
            'type' => 'budget',
            'title' => 'Most Budget-Friendly',
            'option' => $cheapest['name'],
            'reason' => 'Lowest total cost at ' . $cheapest['total_cost'] . ' for ' . $request->trip_duration . ' days',
        ];

        // Comfort recommendation
        $mostComfortable = collect($comparison)->sortByDesc('ratings.comfort')->first();
        $recommendations[] = [
            'type' => 'comfort',
            'title' => 'Most Comfortable',
            'option' => $mostComfortable['name'],
            'reason' => 'Highest comfort rating of ' . $mostComfortable['ratings']['comfort'] . '/5'
        ];

        // Value recommendation
        $bestValue = collect($comparison)->sortByDesc('ratings.value_for_money')->first();
        $recommendations[] = [
            'type' => 'value',
            'title' => 'Best Value for Money',
            'option' => $bestValue['name'],
            'reason' => 'Optimal balance of cost and features with ' . $bestValue['ratings']['value_for_money'] . '/5 value rating'
        ];

        return $recommendations;
    }

    private function getBestBookingTime($transport): string
    {
        switch ($transport->booking_method) {
            case 'street':
                return 'Available anytime, but avoid rush hours (7-9am, 5-7pm) for better availability';
            case 'hotel':
                return 'Book evening before or early morning for same-day travel';
            case 'app':
                return 'Book 15-30 minutes before needed trip';
            case 'rental_shop':
                return 'Visit early morning (8-9am) for best vehicle selection';
            case 'online':
                return 'Book 24-48 hours in advance for better rates and availability';
            default:
                return 'Contact provider directly for best booking times';
        }
    }

    private function getNegotiationTips($transport): array
    {
        $tips = [];

        if ($transport->booking_method === 'street') {
            $tips[] = "Always negotiate before getting in";
            $tips[] = "Know approximate distance to your destination";
            $tips[] = "Have small bills ready (vendors often claim no change)";
            $tips[] = "Be prepared to walk away if price is too high";
            $tips[] = "Group bookings may get better rates";
        } elseif ($transport->booking_method === 'hotel') {
            $tips[] = "Ask hotel for standard rates to nearby destinations";
            $tips[] = "Hotel arrangements are usually fixed price";
            $tips[] = "Negotiate for return trips or full-day rates";
        } else {
            $tips[] = "Fixed pricing - no negotiation typically needed";
            $tips[] = "Look for promotional codes or discounts";
        }

        return $tips;
    }

    private function getSuitabilityInsights($transport): array
    {
        $insights = [];

        // Age group insights
        if ($transport->suitable_for) {
            foreach ($transport->suitable_for as $group) {
                switch ($group) {
                    case 'young_adults':
                        $insights[] = "Great for adventurous travelers aged 15-25";
                        break;
                    case 'families':
                        $insights[] = "Family-friendly with space for children and luggage";
                        break;
                    case 'older_travelers':
                        $insights[] = "Comfortable option for travelers 50+ years";
                        break;
                    case 'budget_travelers':
                        $insights[] = "Excellent value for cost-conscious travelers";
                        break;
                }
            }
        }

        // Capacity insights
        if ($transport->capacity_people <= 2) {
            $insights[] = "Best for solo travelers or couples";
        } elseif ($transport->capacity_people <= 4) {
            $insights[] = "Perfect for small groups and families";
        } else {
            $insights[] = "Great for larger groups and parties";
        }

        // Driver insights
        if ($transport->driver_included) {
            $insights[] = "No driving required - sit back and enjoy the ride";
            $insights[] = "Local driver can provide cultural insights and recommendations";
        } else {
            $insights[] = "Self-drive option gives you complete freedom and flexibility";
            $insights[] = "Requires valid driving license and familiarity with local traffic";
        }

        return $insights;
    }

    private function getSearchSuggestions($query): array
    {
        $suggestions = [];

        // Get similar transport names
        $similarTransports = LocalTransportation::active()
            ->where('name', 'like', '%' . substr($query, 0, 3) . '%')
            ->limit(5)
            ->pluck('name')
            ->toArray();

        // Get related types
        $relatedTypes = LocalTransportation::active()
            ->where('type', 'like', '%' . $query . '%')
            ->distinct()
            ->pluck('type')
            ->toArray();

        return [
            'similar_options' => $similarTransports,
            'related_types' => $relatedTypes,
            'popular_searches' => ['tuk tuk', 'motorbike taxi', 'premium transport', 'self drive', 'group transport']
        ];
    }
}
