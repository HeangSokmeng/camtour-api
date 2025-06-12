<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $destinations = [
            [
                'name' => 'Angkor Wat',
                'description' => 'The largest religious monument in the world, originally constructed as a Hindu temple dedicated to the god Vishnu.',
                'latitude' => 13.4125,
                'longitude' => 103.8670,
                'entrance_fee' => 37.00,
                'transport_fee' => 5.00,
                'nearby_attractions' => [
                    ['name' => 'Angkor Thom', 'distance_km' => 1.7, 'cost' => 0],
                    ['name' => 'Ta Prohm', 'distance_km' => 2.5, 'cost' => 0],
                    ['name' => 'Bakheng Mountain', 'distance_km' => 1.3, 'cost' => 0]
                ],
                'age_recommendations' => ['10-15', '15-20', '20-25', '25-35', '35-50', '50+'],
                'recommended_duration_hours' => 4,
                'best_time_to_visit' => 'Early morning (sunrise) or late afternoon',
                'requires_guide' => false,
                'guide_fee' => 25.00
            ],
            [
                'name' => 'Angkor Thom',
                'description' => 'The last and most enduring capital city of the Khmer empire, famous for the Bayon temple.',
                'latitude' => 13.4413,
                'longitude' => 103.8590,
                'entrance_fee' => 37.00,
                'transport_fee' => 5.00,
                'nearby_attractions' => [
                    ['name' => 'Angkor Wat', 'distance_km' => 1.7, 'cost' => 0],
                    ['name' => 'Bayon Temple', 'distance_km' => 0.5, 'cost' => 0],
                    ['name' => 'Bakheng Mountain', 'distance_km' => 1.8, 'cost' => 0]
                ],
                'age_recommendations' => ['15-20', '20-25', '25-35', '35-50', '50+'],
                'recommended_duration_hours' => 3,
                'best_time_to_visit' => 'Morning to avoid crowds',
                'requires_guide' => true,
                'guide_fee' => 30.00
            ],
            [
                'name' => 'Ta Prohm',
                'description' => 'Famous temple where massive trees grow from the ruins, featured in Tomb Raider movie.',
                'latitude' => 13.4347,
                'longitude' => 103.8890,
                'entrance_fee' => 37.00,
                'transport_fee' => 4.00,
                'nearby_attractions' => [
                    ['name' => 'Angkor Wat', 'distance_km' => 2.5, 'cost' => 0],
                    ['name' => 'Banteay Kdei', 'distance_km' => 0.8, 'cost' => 0]
                ],
                'age_recommendations' => ['10-15', '15-20', '20-25', '25-35', '35-50'],
                'recommended_duration_hours' => 2,
                'best_time_to_visit' => 'Late morning when lighting is optimal',
                'requires_guide' => false,
                'guide_fee' => 20.00
            ],
            [
                'name' => 'Bayon Temple',
                'description' => 'Known for the smiling faces carved into its towers, located in the center of Angkor Thom.',
                'latitude' => 13.4413,
                'longitude' => 103.8590,
                'entrance_fee' => 37.00,
                'transport_fee' => 5.00,
                'nearby_attractions' => [
                    ['name' => 'Angkor Thom', 'distance_km' => 0.5, 'cost' => 0],
                    ['name' => 'Baphuon', 'distance_km' => 0.3, 'cost' => 0]
                ],
                'age_recommendations' => ['15-20', '20-25', '25-35', '35-50', '50+'],
                'recommended_duration_hours' => 2,
                'best_time_to_visit' => 'Mid-morning for best photography',
                'requires_guide' => true,
                'guide_fee' => 25.00
            ],
            [
                'name' => 'Bakheng Mountain',
                'description' => 'Temple mountain offering panoramic views and famous sunset spot.',
                'latitude' => 13.4077,
                'longitude' => 103.8587,
                'entrance_fee' => 37.00,
                'transport_fee' => 3.00,
                'nearby_attractions' => [
                    ['name' => 'Angkor Wat', 'distance_km' => 1.3, 'cost' => 0],
                    ['name' => 'Angkor Thom', 'distance_km' => 1.8, 'cost' => 0]
                ],
                'age_recommendations' => ['15-20', '20-25', '25-35', '35-50'],
                'recommended_duration_hours' => 2,
                'best_time_to_visit' => 'Late afternoon for sunset views',
                'requires_guide' => false,
                'guide_fee' => 15.00
            ]
        ];

        foreach ($destinations as $destination) {
            Destination::create($destination);
        }
    }
}
