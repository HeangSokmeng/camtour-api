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
            [
                'first_name' => 'Chandalen',
                'last_name' => 'Teang',
                'gender' => User::GENDER_MALE,
                'image' => User::DEFAULT_IMAGE,
                'phone' => '0967251109',
                'email' => 'chandalen@gmail.com',
                'password' => '11223344Aa!',
                'role_id' => Role::SYSTEM_ADMIN,
                'create_uid' => 1,
                'update_uid' => 1,
            ],
            [
                'first_name' => 'Chhayya',
                'last_name' => 'Neom',
                'gender' => User::GENDER_MALE,
                'image' => User::DEFAULT_IMAGE,
                'phone' => '0967251109',
                'email' => 'chhayya@gmail.com',
                'password' => '11223344Aa!',
                'role_id' => Role::ADMIN,
                'create_uid' => 1,
                'update_uid' => 1,
            ],
            [
                'first_name' => 'Raksa',
                'last_name' => 'Loun',
                'gender' => User::GENDER_MALE,
                'image' => User::DEFAULT_IMAGE,
                'phone' => '0967251109',
                'email' => 'raksa@gmail.com',
                'password' => '11223344Aa!',
                'role_id' => Role::STAFF,
                'create_uid' => 1,
                'update_uid' => 1,
            ],
            [
                'first_name' => 'Rachana',
                'last_name' => 'Vuth',
                'gender' => User::GENDER_MALE,
                'image' => User::DEFAULT_IMAGE,
                'phone' => '0967251109',
                'email' => 'rachana@gmail.com',
                'password' => '11223344Aa!',
                'role_id' => Role::CUSTOMER,
                'create_uid' => 1,
                'update_uid' => 1,
            ],
        ];
        foreach ($dataUsers as $dataUser) {
            $user = new User($dataUser);
            $user->email_verified_at = Carbon::now();
            $user->save();
            $user->roles()->attach($dataUser['role_id']);
        }
    }
}
