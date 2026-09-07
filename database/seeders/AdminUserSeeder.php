<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminName = env('ADMIN_NAME', 'Aanirudh');
        $adminEmail = env('ADMIN_EMAIL', 'aanirudhch@gmail.com');
        $adminPhone = env('ADMIN_PHONE', '7010186524');
        $adminPassword = env('ADMIN_PASSWORD', 'Raja_Raja02');

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'phone' => $adminPhone,
                'password' => Hash::make($adminPassword),
                'timezone' => env('DEFAULT_TIMEZONE', 'Asia/Kolkata'),
                'currency' => env('DEFAULT_CURRENCY', 'INR'),
                'is_active' => true,
            ]
        );

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
