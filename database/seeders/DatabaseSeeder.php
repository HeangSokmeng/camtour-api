<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            AddressSeeder::class,
        ]);
    }
}
