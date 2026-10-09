<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::insert([
            ['name' => 'tickets.view', 'description' => 'View tickets'],
            ['name' => 'tickets.create', 'description' => 'Create tickets'],
            ['name' => 'tickets.update', 'description' => 'Update tickets'],
            ['name' => 'tickets.delete', 'description' => 'Delete tickets'],
            ['name' => 'tickets.assign', 'description' => 'Assign tickets'],
            ['name' => 'tickets.resolve', 'description' => 'Resolve tickets'],
            ['name' => 'tickets.close', 'description' => 'Close tickets'],
            ['name' => 'tickets.reopen', 'description' => 'Reopen tickets'],
            ['name' => 'tickets.internal_notes', 'description' => 'Manage internal notes'],
            ['name' => 'users.view', 'description' => 'View users'],
            ['name' => 'users.create', 'description' => 'Create users'],
            ['name' => 'users.update', 'description' => 'Update users'],
            ['name' => 'users.delete', 'description' => 'Delete users'],
            ['name' => 'reports.view', 'description' => 'View reports'],
            ['name' => 'settings.manage', 'description' => 'Manage system settings'],
        ]);
    }
}