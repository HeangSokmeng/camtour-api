<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LocalTransportation;

class LocalTransportationSeeder extends Seeder
{
    public function run()
    {
        $transportations = [
            [
                'type' => 'motorbike',
                'name' => 'Motorbike Taxi (Moto)',
                'description' => 'Quick and affordable way to get around, driver included',
                'price_per_hour' => null,
                'price_per_day' => null,
                'price_per_trip' => 2.00,
                'capacity_people' => 1,
                'suitable_for' => ['solo_travelers', 'couples', 'young_adults'],
                'advantages' => [
                    'Very affordable',
                    'Fast through traffic',
                    'Available everywhere',
                    'Good for short distances'
                ],
                'disadvantages' => [
                    'Only 1 passenger',
                    'Safety concerns',
                    'No luggage space',
                    'Weather dependent'
                ],
                'booking_method' => 'street',
                'driver_included' => true
            ],
            [
                'type' => 'motorbike',
                'name' => 'Self-Drive Motorbike',
                'description' => 'Rent a motorbike to drive yourself around Siem Reap',
                'price_per_hour' => 3.00,
                'price_per_day' => 12.00,
                'price_per_trip' => null,
                'capacity_people' => 2,
                'suitable_for' => ['experienced_drivers', 'adventurous_travelers'],
                'advantages' => [
                    'Complete freedom',
                    'Affordable for full day',
                    'Can stop anywhere',
                    'Authentic experience'
                ],
                'disadvantages' => [
                    'Requires driving license',
                    'Traffic can be chaotic',
                    'Safety responsibility',
                    'Fuel costs extra'
                ],
                'booking_method' => 'rental_shop',
                'driver_included' => false
            ],
            [
                'type' => 'tuk_tuk',
                'name' => 'Traditional Tuk-Tuk',
                'description' => 'Three-wheeled vehicle, perfect for sightseeing with driver',
                'price_per_hour' => 3.75,
                'price_per_day' => 30.00,
                'price_per_trip' => 8.00,
                'capacity_people' => 4,
                'suitable_for' => ['families', 'groups', 'tourists', 'all_ages'],
                'advantages' => [
                    'Comfortable seating',
                    'Good for groups',
                    'Driver is tour guide',
                    'Iconic experience',
                    'Luggage space'
                ],
                'disadvantages' => [
                    'More expensive than moto',
                    'Slower in traffic',
                    'Open to weather',
                    'Negotiation required'
                ],
                'booking_method' => 'street',
                'driver_included' => true
            ],
            [
                'type' => 'tuk_tuk',
                'name' => 'Premium Tuk-Tuk',
                'description' => 'Newer, more comfortable tuk-tuk with better seating and shade',
                'price_per_hour' => 20.00,
                'price_per_day' => 120.00,
                'price_per_trip' => 12.00,
                'capacity_people' => 4,
                'suitable_for' => ['families', 'older_travelers', 'comfort_seekers'],
                'advantages' => [
                    'More comfortable',
                    'Better protection from sun',
                    'Professional drivers',
                    'Fixed rates',
                    'English speaking driver'
                ],
                'disadvantages' => [
                    'Higher cost',
                    'Less authentic',
                    'Limited availability'
                ],
                'booking_method' => 'hotel',
                'driver_included' => true
            ],
            [
                'type' => 'tricycle',
                'name' => 'Bicycle Tricycle (Cyclo)',
                'description' => 'Traditional pedal-powered tricycle, slow but authentic',
                'price_per_hour' => 8.00,
                'price_per_day' => 40.00,
                'price_per_trip' => 5.00,
                'capacity_people' => 2,
                'suitable_for' => ['culture_enthusiasts', 'slow_travelers', 'photographers'],
                'advantages' => [
                    'Authentic cultural experience',
                    'Eco-friendly',
                    'Quiet and peaceful',
                    'Great for photography',
                    'Support local livelihoods'
                ],
                'disadvantages' => [
                    'Very slow',
                    'Limited distance',
                    'Weather dependent',
                    'Physical effort for driver',
                    'Not suitable for long trips'
                ],
                'booking_method' => 'street',
                'driver_included' => true
            ],
            [
                'type' => 'tricycle',
                'name' => 'Electric Tricycle',
                'description' => 'Modern electric-powered tricycle, environmentally friendly',
                'price_per_hour' => 12.00,
                'price_per_day' => 60.00,
                'price_per_trip' => 7.00,
                'capacity_people' => 3,
                'suitable_for' => ['eco_travelers', 'families', 'comfort_seekers'],
                'advantages' => [
                    'Environmentally friendly',
                    'Quieter than motorbikes',
                    'More comfortable than cyclo',
                    'Moderate speed',
                    'Weather protection'
                ],
                'disadvantages' => [
                    'Limited availability',
                    'Battery range limits',
                    'Higher cost than cyclo',
                    'Still slower than tuk-tuk'
                ],
                'booking_method' => 'app',
                'driver_included' => true
            ]
        ];

        foreach ($transportations as $transport) {
            LocalTransportation::create($transport);
        }
    }
}
