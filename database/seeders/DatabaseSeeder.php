<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            AddressSeeder::class,
            QuestionChatBotSeeder::class,

            QuestionSeeder::class,
            DestinationSeeder::class,
            HotelSeeder::class,
            TransportationCostSeeder::class,
            LocalTransportationSeeder::class,
            MealSeeder::class,
        ]);
    }
}
