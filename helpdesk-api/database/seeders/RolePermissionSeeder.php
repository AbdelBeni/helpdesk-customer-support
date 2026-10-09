<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'Admin')->firstOrFail();
        $agent = Role::where('name', 'Agent')->firstOrFail();
        $customer = Role::where('name', 'Customer')->firstOrFail();

        $permissions = Permission::pluck('id', 'name');

        $admin->permissions()->sync($permissions->values());

        $agent->permissions()->sync(
            $permissions->only([
                'tickets.view',
                'tickets.create',
                'tickets.update',
                'tickets.assign',
                'tickets.resolve',
                'tickets.close',
                'tickets.reopen',
                'tickets.internal_notes',
            ])->values()
        );

        $customer->permissions()->sync(
            $permissions->only([
                'tickets.view',
                'tickets.create',
            ])->values()
        );
    }
}