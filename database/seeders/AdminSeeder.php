<?php

namespace Database\Seeders;

use App\Domain\Admin\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name'      => 'Super Admin',
                'password'  => 'Admin12345!',
                'role'      => 'super_admin',
                'is_active' => true,
            ],
        );
    }
}
