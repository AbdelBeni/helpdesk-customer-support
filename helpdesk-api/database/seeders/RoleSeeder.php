<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            [
                'name' => 'Admin',
                'description' => 'System administrator',
            ],
            [
                'name' => 'Agent',
                'description' => 'Customer support agent',
            ],
            [
                'name' => 'Customer',
                'description' => 'Support customer',
            ],
        ]);
    }
}