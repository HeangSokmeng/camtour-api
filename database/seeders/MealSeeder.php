<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meal;

class MealSeeder extends Seeder
{
    public function run()
    {
        $meals = [
            // Breakfast Options
            [
                'category' => 'breakfast',
                'name' => 'Khmer Traditional Breakfast',
                'description' => 'Rice porridge (bobor) with fish or pork, served with pickled vegetables',
                'price_per_person' => 3.50,
                'cuisine_type' => 'khmer',
                'meal_type' => 'street_food',
                'dietary_options' => ['halal_option'],
                'preparation_time_minutes' => 15,
                'location_type' => 'local_market',
                'is_popular' => true
            ],
            [
                'category' => 'breakfast',
                'name' => 'Western Breakfast',
                'description' => 'Eggs, toast, bacon, coffee - hotel or western restaurant style',
                'price_per_person' => 8.00,
                'cuisine_type' => 'western',
                'meal_type' => 'restaurant',
                'dietary_options' => ['vegetarian_option'],
                'preparation_time_minutes' => 20,
                'location_type' => 'hotel',
                'is_popular' => false
            ],
            [
                'category' => 'breakfast',
                'name' => 'Nom Banh Chok',
                'description' => 'Khmer noodles with fish curry sauce and fresh vegetables',
                'price_per_person' => 2.50,
                'cuisine_type' => 'khmer',
                'meal_type' => 'street_food',
                'dietary_options' => [],
                'preparation_time_minutes' => 10,
                'location_type' => 'local_market',
                'is_popular' => true
            ],

            // Lunch Options
            [
                'category' => 'lunch',
                'name' => 'Amok Fish',
                'description' => 'Traditional Khmer curry steamed in banana leaf with coconut milk',
                'price_per_person' => 6.50,
                'cuisine_type' => 'khmer',
                'meal_type' => 'restaurant',
                'dietary_options' => ['gluten_free'],
                'preparation_time_minutes' => 30,
                'location_type' => 'restaurant',
                'is_popular' => true
            ],
            [
                'category' => 'lunch',
                'name' => 'Lok Lak',
                'description' => 'Stir-fried beef with onions, served over rice with dipping sauce',
                'price_per_person' => 7.00,
                'cuisine_type' => 'khmer',
                'meal_type' => 'restaurant',
                'dietary_options' => [],
                'preparation_time_minutes' => 25,
                'location_type' => 'restaurant',
                'is_popular' => true
            ],
            [
                'category' => 'lunch',
                'name' => 'International Cuisine',
                'description' => 'Pizza, pasta, burgers or other international dishes',
                'price_per_person' => 12.00,
                'cuisine_type' => 'western',
                'meal_type' => 'restaurant',
                'dietary_options' => ['vegetarian_option', 'vegan_option'],
                'preparation_time_minutes' => 35,
                'location_type' => 'restaurant',
                'is_popular' => false
            ],

            // Dinner Options
            [
                'category' => 'dinner',
                'name' => 'BBQ Buffet',
                'description' => 'All-you-can-eat BBQ with beer, popular tourist activity',
                'price_per_person' => 15.00,
                'cuisine_type' => 'mixed',
                'meal_type' => 'restaurant',
                'dietary_options' => ['vegetarian_option'],
                'preparation_time_minutes' => 60,
                'location_type' => 'restaurant',
                'is_popular' => true
            ],
            [
                'category' => 'dinner',
                'name' => 'Fine Dining Khmer',
                'description' => 'Upscale traditional Khmer cuisine in elegant restaurant',
                'price_per_person' => 25.00,
                'cuisine_type' => 'khmer',
                'meal_type' => 'restaurant',
                'dietary_options' => ['vegetarian_option', 'vegan_option'],
                'preparation_time_minutes' => 45,
                'location_type' => 'restaurant',
                'is_popular' => false
            ],
            [
                'category' => 'dinner',
                'name' => 'Street Food Dinner',
                'description' => 'Local street food experience - grilled meats, soups, noodles',
                'price_per_person' => 5.00,
                'cuisine_type' => 'khmer',
                'meal_type' => 'street_food',
                'dietary_options' => ['halal_option'],
                'preparation_time_minutes' => 20,
                'location_type' => 'local_market',
                'is_popular' => true
            ],

            // Snack Options
            [
                'category' => 'snack',
                'name' => 'Fresh Coconut Water',
                'description' => 'Fresh coconut water and meat, perfect for hot weather',
                'price_per_person' => 1.50,
                'cuisine_type' => 'local',
                'meal_type' => 'street_food',
                'dietary_options' => ['vegan', 'gluten_free'],
                'preparation_time_minutes' => 5,
                'location_type' => 'local_market',
                'is_popular' => true
            ],
            [
                'category' => 'snack',
                'name' => 'Tropical Fruits',
                'description' => 'Fresh tropical fruits - mango, dragon fruit, rambutan',
                'price_per_person' => 2.00,
                'cuisine_type' => 'local',
                'meal_type' => 'street_food',
                'dietary_options' => ['vegan', 'gluten_free'],
                'preparation_time_minutes' => 5,
                'location_type' => 'local_market',
                'is_popular' => true
            ]
        ];

        foreach ($meals as $meal) {
            Meal::create($meal);
        }
    }
}
