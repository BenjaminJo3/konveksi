<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        User::query()->firstOrCreate(
            ['email' => 'superadmin@konveksi.test'],
            [
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => Hash::make(config('app.demo_password')),
            ],
        );
    }
}
