<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataRoles = [
            ['id' => Role::REGULAR_USER, 'name' => 'User'],
            ['id' => Role::SYSTEM_ADMIN, 'name' => 'System Admin'],
        ];
        foreach ($dataRoles as $dataRole) {
            $role = new Role($dataRole);
            $role->id = $dataRole['id'];
            $role->save();
        }
    }
}
