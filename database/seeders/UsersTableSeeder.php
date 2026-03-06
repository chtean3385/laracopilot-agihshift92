<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'           => 'Super Admin',
                'email'          => 'superadmin@gmail.com',
                'password'       => Hash::make('Super@#3385'),
                'role'           => 'Super Admin',
                'is_super_admin' => true,
                'status'         => 'active',
            ],
            [
                'name'           => 'Admin User',
                'email'          => 'admin@resort.com',
                'password'       => Hash::make('admin123'),
                'role'           => 'Admin',
                'is_super_admin' => false,
                'status'         => 'active',
            ],
            [
                'name'           => 'Resort Manager',
                'email'          => 'manager@resort.com',
                'password'       => Hash::make('manager123'),
                'role'           => 'Manager',
                'is_super_admin' => false,
                'status'         => 'active',
            ],
            [
                'name'           => 'Front Desk',
                'email'          => 'receptionist@resort.com',
                'password'       => Hash::make('recept123'),
                'role'           => 'Receptionist',
                'is_super_admin' => false,
                'status'         => 'active',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }
    }
}
