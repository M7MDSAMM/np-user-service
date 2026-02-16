<?php

namespace Database\Seeders;

use App\Domain\Admin\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->firstOrCreate(
            ['email' => 'admin@local.test'],
            [
                'uuid'      => (string) Str::uuid(),
                'name'      => 'Super Admin',
                'password'  => 'Admin12345!',
                'role'      => 'super_admin',
                'is_active' => true,
            ],
        );
    }
}
