<?php
// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $dataRoles = [
            ['id' => Role::CUSTOMER, 'name' => 'Customer'],
            ['id' => Role::STAFF, 'name' => 'Staff'],
            ['id' => Role::ADMIN, 'name' => 'Admin'],
            ['id' => Role::SYSTEM_ADMIN, 'name' => 'System Admin'],
        ];

        foreach ($dataRoles as $dataRole) {
            Role::updateOrCreate(['id' => $dataRole['id']], ['name' => $dataRole['name']]);
        }
    }
}

