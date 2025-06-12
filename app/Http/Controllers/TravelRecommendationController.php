<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\TravelRequest;
use App\Models\TransportationCost;
use App\Models\Meal;
use App\Models\LocalTransportation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TravelRecommendationController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'budget' => 'required|numeric|min:1'
        ]);
        $sessionId = Str::uuid();
        $travelRequest = TravelRequest::create([
            'budget' => $request->budget,
            'session_id' => $sessionId
        ]);
        $data = [
            'session_id' => $sessionId,
            'budget' => $request->budget,
            'next_step' => 'questions'
        ];
        return res_success('Travel recommendation started successfully.', $data);
    }

    /**
     * Get all questions with options
     */
    public function getQuestions(): JsonResponse
    {
        $questions = Question::active()
            ->with('activeOptions')
            ->orderBy('sort_order')
            ->get();
        if ($questions->isEmpty())  return res_fail('No questions found.', [], 1, 404);
        return res_success('Questions retrieved successfully.', $questions);
    }

    /**
     * Get question by type
     */
    public function getQuestionByType($type): JsonResponse
    {
        $question = Question::active()
            ->byType($type)
            ->with('activeOptions')
            ->first();
        if (!$question)  return res_fail('Question not found.', [], 1, 404);
        return res_success('Question retrieved successfully.', $question);
    }

    /**
     * Submit answers and get next question or recommendation
     */
    public function submitAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'question_type' => 'required|string',
            'answer' => 'required'
        ]);
        $travelRequest = TravelRequest::where('session_id', $request->session_id)->first();
        if (!$travelRequest) return res_fail('Travel session not found.', [], 1, 404);
        $this->updateTravelRequest($travelRequest, $request->question_type, $request->answer);
        if ($this->allQuestionsAnswered($travelRequest)) {
            $recommendation = $this->generateRecommendation($travelRequest);
            return res_success('Recommendation generated successfully.', $recommendation);
        }
        $nextQuestion = $this->getNextQuestion($travelRequest);
        if ($nextQuestion) return res_success('Next question retrieved successfully.', $nextQuestion);
        return res_success('All questions completed. Processing recommendation...', [
            'status' => 'processing',
            'session_id' => $request->session_id
        ]);
    }

    /**
     * Get transportation costs
     */
    public function getTransportationCosts(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
            'type' => 'required|string'
        ]);
        $cost = TransportationCost::active()
            ->byRoute($request->from, $request->to, $request->type)
            ->first();
        if (!$cost)  return res_fail('Transportation cost not found.', [], 1, 404);
        return res_success('Transportation cost retrieved successfully.', $cost);
    }
    /**
     * Get destinations
     */
    public function getDestinations(): JsonResponse
    {
        $destinations = Destination::active()
            ->orderBy('name')
            ->paginate(10);
        return res_paginate($destinations, 'Destinations retrieved successfully.', $destinations);
    }
    /**
     * Get hotels by star rating
     */
    public function getHotels(Request $request): JsonResponse
    {
        $query = Hotel::active();
        if ($request->has('star_rating')) $query->byStar($request->star_rating);
        $hotels = $query->orderBy('price_per_night')->paginate(10);
        return res_paginate($hotels, 'Hotels retrieved successfully.', $hotels);
    }
    /**
     * Get recommendation by session ID
     */
    public function getRecommendation($sessionId): JsonResponse
    {
        $travelRequest = TravelRequest::where('session_id', $sessionId)->first();
        if (!$travelRequest) return res_fail('Travel session not found.', [], 1, 404);
        if (!$travelRequest->recommendation) {
            return res_fail('Recommendation not yet generated.', [], 1, 404);
        }
        $data = [
            'session_id' => $sessionId,
            'budget' => $travelRequest->budget,
            'total_estimated_cost' => $travelRequest->total_estimated_cost,
            'recommendation' => json_decode($travelRequest->recommendation, true),
            'itinerary' => $travelRequest->recommended_itinerary
        ];
        return res_success('Recommendation retrieved successfully.', $data);
    }

    /**
     * Private helper methods
     */
    private function updateTravelRequest($travelRequest, $questionType, $answer)
    {
        $userAnswers = $travelRequest->user_answers ?? [];
        $userAnswers[$questionType] = $answer;
        $updateData = ['user_answers' => $userAnswers];
        switch ($questionType) {
            case 'transportation':
                $updateData['transportation'] = $answer;
                break;
            case 'departure':
                $updateData['departure_location'] = $answer;
                break;
            case 'duration':
                $updateData['trip_duration'] = $answer;
                break;
            case 'party_size':
                $updateData['party_size'] = $answer;
                break;
            case 'age_range':
                $updateData['age_range'] = $answer;
                break;
            case 'destination':
                $updateData['primary_destination'] = $answer;
                break;
            case 'local_transportation':
                $updateData['local_transportation'] = $answer;
                break;
            case 'meal_preference':
                $updateData['meal_preference'] = $answer;
                break;
            case 'hotel':
                $updateData['hotel_preference'] = $answer;
                break;
        }
        $travelRequest->update($updateData);
    }

    private function allQuestionsAnswered($travelRequest): bool
    {
        $requiredQuestions = [
            'transportation',
            'departure',
            'duration',
            'party_size',
            'age_range',
            'destination',
            'local_transportation',
            'meal_preference',
            'hotel'
        ];
        $userAnswers = $travelRequest->user_answers ?? [];
        foreach ($requiredQuestions as $question) {
            if (!isset($userAnswers[$question]) || empty($userAnswers[$question])) {
                return false;
            }
        }
        return true;
    }

    private function getNextQuestion($travelRequest)
    {
        $questionOrder = [
            'transportation',
            'departure',
            'duration',
            'party_size',
            'age_range',
            'destination',
            'local_transportation',
            'meal_preference',
            'hotel'
        ];
        $userAnswers = $travelRequest->user_answers ?? [];
        foreach ($questionOrder as $questionType) {
            if (!isset($userAnswers[$questionType]) || empty($userAnswers[$questionType])) {
                return Question::active()
                    ->byType($questionType)
                    ->with('activeOptions')
                    ->first();
            }
        }
        return null;
    }

    private function generateRecommendation($travelRequest)
    {
        $transportationCost = $this->calculateTransportationCost($travelRequest);
        $destination = Destination::where('name', 'like', '%' . str_replace('_', ' ', $travelRequest->primary_destination) . '%')->first();
        $destinationCost = $destination ? $destination->total_cost * $travelRequest->party_size : 0;
        $hotelCost = $this->calculateHotelCost($travelRequest);
        $mealCost = $this->calculateMealCost($travelRequest);
        $localTransportCost = $this->calculateLocalTransportCost($travelRequest);
        $totalCost = $transportationCost + $destinationCost + $hotelCost + $mealCost + $localTransportCost;
        $recommendations = [];
        if ($totalCost <= $travelRequest->budget) {
            $recommendations[] = [
                'type' => 'recommended',
                'title' => 'Complete Travel Package Within Budget',
                'description' => "Perfect! You can enjoy " . ucfirst(str_replace('_', ' ', $travelRequest->primary_destination)) . " with all your preferences.",
                'cost' => $totalCost,
                'includes' => [
                    'Long-distance transportation: $' . number_format($transportationCost, 2),
                    'Destination fees: $' . number_format($destinationCost, 2),
                    'Hotel (' . $travelRequest->trip_duration . ' nights): $' . number_format($hotelCost, 2),
                    'Meals (' . $travelRequest->trip_duration . ' days): $' . number_format($mealCost, 2),
                    'Local transportation: $' . number_format($localTransportCost, 2)
                ]
            ];
            if ($destination && $destination->nearby_attractions) {
                $remainingBudget = $travelRequest->budget - $totalCost;
                $nearbyAttractions = $this->getBudgetFriendlyNearbyAttractions($destination, $remainingBudget, $travelRequest->party_size);
                if (!empty($nearbyAttractions)) {
                    $recommendations[] = [
                        'type' => 'bonus',
                        'title' => 'Bonus Attractions Within Budget',
                        'description' => 'You still have budget for these nearby attractions:',
                        'attractions' => $nearbyAttractions,
                        'remaining_budget' => $remainingBudget
                    ];
                }
            }
        } else {
            $overBudget = $totalCost - $travelRequest->budget;
            $suggestions = $this->generateBudgetAlternatives($travelRequest, $overBudget);
            $recommendations[] = [
                'type' => 'budget_alternative',
                'title' => 'Budget-Friendly Alternatives',
                'description' => "Your budget is $" . number_format($travelRequest->budget, 2) . " but estimated cost is $" . number_format($totalCost, 2) . " ($" . number_format($overBudget, 2) . " over budget)",
                'suggestions' => $suggestions,
                'cost_breakdown' => [
                    'transportation' => $transportationCost,
                    'destinations' => $destinationCost,
                    'hotels' => $hotelCost,
                    'meals' => $mealCost,
                    'local_transport' => $localTransportCost
                ]
            ];
        }
        $itinerary = $this->generateEnhancedItinerary($travelRequest, $destination);
        $travelRequest->update([
            'recommendation' => json_encode($recommendations),
            'total_estimated_cost' => $totalCost,
            'total_meal_cost' => $mealCost,
            'total_local_transport_cost' => $localTransportCost,
            'recommended_itinerary' => $itinerary
        ]);
        return [
            'session_id' => $travelRequest->session_id,
            'budget' => $travelRequest->budget,
            'total_estimated_cost' => $totalCost,
            'cost_breakdown' => [
                'transportation' => $transportationCost,
                'destinations' => $destinationCost,
                'hotels' => $hotelCost,
                'meals' => $mealCost,
                'local_transport' => $localTransportCost
            ],
            'recommendations' => $recommendations,
            'itinerary' => $itinerary,
            'budget_status' => $totalCost <= $travelRequest->budget ? 'within_budget' : 'over_budget'
        ];
    }

    private function calculateTransportationCost($travelRequest)
    {
        $cost = TransportationCost::active()
            ->byRoute($travelRequest->departure_location, 'Siem Reap', $travelRequest->transportation)
            ->first();
        return $cost ? $cost->cost * $travelRequest->party_size : 0;
    }

    private function calculateHotelCost($travelRequest)
    {
        $starRating = (int) str_replace('star', '', $travelRequest->hotel_preference);
        $hotel = Hotel::active()->byStar($starRating)->orderBy('price_per_night')->first();
        if (!$hotel) {
            $defaultPrices = ['1' => 15, '2' => 35, '3' => 80];
            $pricePerNight = $defaultPrices[$starRating] ?? 35;
        } else {
            $pricePerNight = $hotel->price_per_night;
        }
        $roomsNeeded = ceil($travelRequest->party_size / 2); // Assuming 2 people per room
        return $pricePerNight * $roomsNeeded * $travelRequest->trip_duration;
    }

    private function calculateMealCost($travelRequest): float
    {
        $dailyBudgets = [
            'budget_local' => 6.5,
            'mixed_dining' => 15.0,
            'comfort_dining' => 25.0,
            'premium_dining' => 42.5
        ];
        $dailyBudget = $dailyBudgets[$travelRequest->meal_preference] ?? 15.0;
        return $dailyBudget * $travelRequest->party_size * $travelRequest->trip_duration;
    }

    private function calculateLocalTransportCost($travelRequest): float
    {
        $transportMapping = [
            'motorbike_taxi' => 'Motorbike Taxi',
            'self_drive_motorbike' => 'Self-Drive Motorbike',
            'tuk_tuk' => 'Traditional Tuk-Tuk',
            'premium_tuk_tuk' => 'Premium Tuk-Tuk',
            'tricycle' => 'Bicycle Tricycle',
            'electric_tricycle' => 'Electric Tricycle'
        ];
        $transportName = $transportMapping[$travelRequest->local_transportation] ?? 'Traditional Tuk-Tuk';
        $transport = LocalTransportation::active()
            ->where('name', 'like', '%' . $transportName . '%')
            ->first();
        if (!$transport) {
            $defaultCosts = [
                'motorbike_taxi' => 8,
                'self_drive_motorbike' => 12,
                'tuk_tuk' => 30,
                'premium_tuk_tuk' => 50,
                'tricycle' => 40,
                'electric_tricycle' => 60
            ];
            $dailyCost = $defaultCosts[$travelRequest->local_transportation] ?? 30;
        } else {
            $dailyCost = $transport->estimated_daily_cost;
        }
        return $dailyCost * $travelRequest->trip_duration;
    }

    private function generateBudgetAlternatives($travelRequest, $overBudget): array
    {
        $suggestions = [];
        if ($travelRequest->meal_preference !== 'budget_local') {
            $mealSavings = $this->calculateMealCost($travelRequest) -
                          (6.5 * $travelRequest->party_size * $travelRequest->trip_duration);
            $suggestions[] = "Switch to local street food and save $" . number_format($mealSavings, 2);
        }
        $currentLocalTransportCost = $this->calculateLocalTransportCost($travelRequest);
        $motorbikeOption = LocalTransportation::where('type', 'motorbike')->orderBy('estimated_daily_cost')->first();
        if ($motorbikeOption) {
            $motorbikeCost = $motorbikeOption->estimated_daily_cost * $travelRequest->trip_duration;
            if ($motorbikeCost < $currentLocalTransportCost) {
                $savings = $currentLocalTransportCost - $motorbikeCost;
                $suggestions[] = "Use motorbike taxi instead and save $" . number_format($savings, 2);
            }
        }
        if ($travelRequest->hotel_preference !== 'star1') {
            $suggestions[] = "Consider a lower star rating hotel to reduce accommodation costs";
        }
        if ($travelRequest->trip_duration > 1) {
            $shorterTripSavings = ($this->calculateMealCost($travelRequest) + $this->calculateLocalTransportCost($travelRequest)) / $travelRequest->trip_duration;
            $suggestions[] = "Reduce trip by 1 day to save approximately $" . number_format($shorterTripSavings, 2);
        }
        return $suggestions;
    }

    private function generateEnhancedItinerary($travelRequest, $destination): array
    {
        $itinerary = [];
        $mealPlan = $this->generateDailyMealPlan($travelRequest);
        $transportInfo = $this->getLocalTransportInfo($travelRequest);
        for ($day = 1; $day <= $travelRequest->trip_duration; $day++) {
            $dayPlan = [
                'day' => $day,
                'title' => "Day {$day}",
                'activities' => [],
                'meals' => $mealPlan[$day - 1] ?? [],
                'transportation' => $transportInfo,
                'estimated_daily_cost' => 0
            ];
            if ($day == 1) {
                $dayPlan['activities'][] = [
                    'time' => '08:00',
                    'activity' => 'Departure from ' . ucfirst(str_replace('_', ' ', $travelRequest->departure_location)),
                    'duration' => '2-3 hours',
                    'transport' => ucfirst($travelRequest->transportation),
                    'cost' => $this->calculateTransportationCost($travelRequest) / $travelRequest->party_size
                ];
                $dayPlan['activities'][] = [
                    'time' => '11:00',
                    'activity' => 'Arrive in Siem Reap, Check-in hotel',
                    'duration' => '1 hour',
                    'transport' => 'Walking',
                    'cost' => 0
                ];
                $dayPlan['activities'][] = [
                    'time' => '14:00',
                    'activity' => 'Visit ' . ucfirst(str_replace('_', ' ', $travelRequest->primary_destination)),
                    'duration' => $destination ? $destination->recommended_duration_hours . ' hours' : '3 hours',
                    'transport' => ucfirst(str_replace('_', ' ', $travelRequest->local_transportation)),
                    'cost' => $destination ? $destination->total_cost : 50
                ];
                $dayPlan['activities'][] = [
                    'time' => '18:00',
                    'activity' => 'Return to hotel area, explore local markets',
                    'duration' => '2 hours',
                    'transport' => ucfirst(str_replace('_', ' ', $travelRequest->local_transportation)),
                    'cost' => 0
                ];
            } elseif ($day == $travelRequest->trip_duration) {
                $dayPlan['activities'][] = [
                    'time' => '08:00',
                    'activity' => 'Final breakfast and souvenir shopping',
                    'duration' => '1.5 hours',
                    'transport' => 'Walking',
                    'cost' => 0
                ];
                $dayPlan['activities'][] = [
                    'time' => '10:00',
                    'activity' => 'Check-out and departure preparation',
                    'duration' => '1 hour',
                    'transport' => 'Walking',
                    'cost' => 0
                ];
                $dayPlan['activities'][] = [
                    'time' => '11:00',
                    'activity' => 'Return journey to ' . ucfirst(str_replace('_', ' ', $travelRequest->departure_location)),
                    'duration' => '2-3 hours',
                    'transport' => ucfirst($travelRequest->transportation),
                    'cost' => 0
                ];
            } else {
                $dayPlan['activities'][] = [
                    'time' => '08:00',
                    'activity' => 'Morning temple exploration',
                    'duration' => '4 hours',
                    'transport' => ucfirst(str_replace('_', ' ', $travelRequest->local_transportation)),
                    'cost' => 40
                ];
                $dayPlan['activities'][] = [
                    'time' => '13:00',
                    'activity' => 'Rest and lunch break',
                    'duration' => '1.5 hours',
                    'transport' => 'Walking',
                    'cost' => 0
                ];
                $dayPlan['activities'][] = [
                    'time' => '15:00',
                    'activity' => 'Visit nearby attractions or cultural sites',
                    'duration' => '3 hours',
                    'transport' => ucfirst(str_replace('_', ' ', $travelRequest->local_transportation)),
                    'cost' => 30
                ];
                $dayPlan['activities'][] = [
                    'time' => '18:30',
                    'activity' => 'Local market visit and cultural experience',
                    'duration' => '2 hours',
                    'transport' => 'Walking',
                    'cost' => 5
                ];
            }
            $dayPlan['estimated_daily_cost'] = array_sum(array_column($dayPlan['activities'], 'cost'));
            $itinerary[] = $dayPlan;
        }
        return $itinerary;
    }

    private function generateDailyMealPlan($travelRequest): array
    {
        $mealPlan = [];
        $preference = $travelRequest->meal_preference;
        for ($day = 1; $day <= $travelRequest->trip_duration; $day++) {
            $dayMeals = [
                'breakfast' => $this->selectMealByPreference('breakfast', $preference),
                'lunch' => $this->selectMealByPreference('lunch', $preference),
                'dinner' => $this->selectMealByPreference('dinner', $preference),
                'snack' => $this->selectMealByPreference('snack', $preference)
            ];
            $mealPlan[] = array_filter($dayMeals); // Remove null values
        }

        return $mealPlan;
    }

    private function getLocalTransportInfo($travelRequest): array
    {
        $transportMapping = [
            'motorbike_taxi' => 'Motorbike Taxi',
            'self_drive_motorbike' => 'Self-Drive Motorbike',
            'tuk_tuk' => 'Traditional Tuk-Tuk',
            'premium_tuk_tuk' => 'Premium Tuk-Tuk',
            'tricycle' => 'Bicycle Tricycle',
            'electric_tricycle' => 'Electric Tricycle'
        ];
        $transportName = $transportMapping[$travelRequest->local_transportation] ?? 'Traditional Tuk-Tuk';
        $transport = LocalTransportation::active()
            ->where('name', 'like', '%' . $transportName . '%')
            ->first();
        if (!$transport) {
            return [
                'type' => $travelRequest->local_transportation,
                'daily_cost' => 80,
                'description' => 'Local transportation around Siem Reap'
            ];
        }
        return [
            'type' => $transport->type,
            'name' => $transport->name,
            'daily_cost' => $transport->estimated_daily_cost,
            'description' => $transport->description,
            'capacity' => $transport->capacity_people,
            'advantages' => $transport->advantages,
            'booking_method' => $transport->booking_method
        ];
    }

    private function selectMealByPreference($category, $preference)
    {
        $query = Meal::active()->byCategory($category);
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
            'name' => $meal->name,
            'description' => $meal->description,
            'price_per_person' => $meal->price_per_person,
            'cuisine_type' => $meal->cuisine_type,
            'location_type' => $meal->location_type
        ];
    }

    private function getBudgetFriendlyNearbyAttractions($destination, $remainingBudget, $partySize): array
    {
        $nearbyAttractions = [];
        if (!$destination->nearby_attractions) return $nearbyAttractions;
        foreach ($destination->nearby_attractions as $attraction) {
            $attractionCost = ($attraction['cost'] ?? 0) * $partySize;
            if ($attractionCost <= $remainingBudget) {
                $nearbyAttractions[] = [
                    'name' => $attraction['name'],
                    'distance_km' => $attraction['distance_km'] ?? 0,
                    'cost_per_person' => $attraction['cost'] ?? 0,
                    'total_cost' => $attractionCost,
                    'description' => 'Beautiful nearby attraction worth visiting'
                ];
                $remainingBudget -= $attractionCost;
            }
        }
        return $nearbyAttractions;
    }
}
