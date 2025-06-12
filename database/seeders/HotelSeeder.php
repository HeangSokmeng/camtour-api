<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hotels = [
            // 1 Star Hotels
            [
                'name' => 'Siem Reap Budget Inn',
                'description' => 'Clean and comfortable budget accommodation',
                'star_rating' => 1,
                'price_per_night' => 15.00,
                'latitude' => 13.3671,
                'longitude' => 103.8448,
                'amenities' => ['WiFi', 'Air Conditioning', 'Private Bathroom'],
                'room_types' => ['Single', 'Double'],
                'contact_phone' => '+855-63-123456',
                'address' => 'Pub Street Area, Siem Reap'
            ],
            [
                'name' => 'Angkor Backpackers',
                'description' => 'Perfect for budget travelers',
                'star_rating' => 1,
                'price_per_night' => 12.00,
                'latitude' => 13.3589,
                'longitude' => 103.8567,
                'amenities' => ['WiFi', 'Shared Kitchen', 'Laundry'],
                'room_types' => ['Dormitory', 'Private Room'],
                'contact_phone' => '+855-63-234567',
                'address' => 'Old Market Area, Siem Reap'
            ],
            // 2 Star Hotels
            [
                'name' => 'Golden Temple Hotel',
                'description' => 'Comfortable mid-range hotel with good amenities',
                'star_rating' => 2,
                'price_per_night' => 35.00,
                'latitude' => 13.3647,
                'longitude' => 103.8523,
                'amenities' => ['WiFi', 'Swimming Pool', 'Restaurant', 'Room Service'],
                'room_types' => ['Standard', 'Deluxe', 'Suite'],
                'contact_phone' => '+855-63-345678',
                'address' => 'Sivatha Boulevard, Siem Reap'
            ],
        ];
        foreach ($hotels as $hotel) {
            Hotel::create($hotel);
        }
    }
}
