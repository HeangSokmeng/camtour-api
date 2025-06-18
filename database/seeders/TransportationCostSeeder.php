<?php

namespace Database\Seeders;

use App\Models\TransportationCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportationCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transportationCosts = [
            // From Phnom Penh
            [
                'from_location' => 'phnom_penh',
                'to_location' => 'Siem Reap',
                'transportation_type' => 'bus',
                'cost' => 15.00,
                'duration_minutes' => 360, // 6 hours
                'notes' => 'Comfortable air-conditioned bus with stops'
            ],
            [
                'from_location' => 'phnom_penh',
                'to_location' => 'Siem Reap',
                'transportation_type' => 'car',
                'cost' => 30.00,
                'duration_minutes' => 300, // 5 hours
                'notes' => 'Private car with driver, direct route'
            ],
            // From Kampong Cham
            [
                'from_location' => 'kampong_cham',
                'to_location' => 'Siem Reap',
                'transportation_type' => 'car',
                'cost' => 12.00,
                'duration_minutes' => 240, // 4 hours
                'notes' => 'Shared taxi or private car'
            ],
            [
                'from_location' => 'kampong_cham',
                'to_location' => 'Siem Reap',
                'transportation_type' => 'bus',
                'cost' => 10.00,
                'duration_minutes' => 300, // 5 hours
                'notes' => 'Local bus service with multiple stops'
            ],
            // Return trips
            [
                'from_location' => 'Siem Reap',
                'to_location' => 'phnom_penh',
                'transportation_type' => 'bus',
                'cost' => 15.00,
                'duration_minutes' => 360,
                'notes' => 'Return trip to Phnom Penh'
            ],
            [
                'from_location' => 'Siem Reap',
                'to_location' => 'phnom_penh',
                'transportation_type' => 'car',
                'cost' => 30.00,
                'duration_minutes' => 300,
                'notes' => 'Return trip to Phnom Penh by car'
            ],
            [
                'from_location' => 'Siem Reap',
                'to_location' => 'kampong_cham',
                'transportation_type' => 'car',
                'cost' => 12.00,
                'duration_minutes' => 240,
                'notes' => 'Return trip to Kampong Cham'
            ],
            [
                'from_location' => 'Siem Reap',
                'to_location' => 'kampong_cham',
                'transportation_type' => 'bus',
                'cost' => 10.00,
                'duration_minutes' => 300,
                'notes' => 'Return trip to Kampong Cham by bus'
            ]
        ];

        foreach ($transportationCosts as $cost) {
            TransportationCost::create($cost);
        }
    }
}
