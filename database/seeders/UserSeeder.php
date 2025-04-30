<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $dataUsers = [
            ['id' => 1, 'first_name' => 'Chandalen', 'last_name' => 'Teang', 'gender' => User::GENDER_MALE, 'image' => User::DEFAULT_IMAGE, 'phone' => '0967251109', 'email' => 'chandalen@gmail.com', 'password' => '11223344Aa!', 'role_id' => Role::SYSTEM_ADMIN],
        ];
        foreach ($dataUsers as $dataUser) {
            $user = new User($dataUser);
            $user->id = $dataUser['id'];
            $user->email_verified_at = Carbon::now();
            $user->save();
        }
    }
}
