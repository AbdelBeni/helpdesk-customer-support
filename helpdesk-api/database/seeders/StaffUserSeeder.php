<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Support',
                'email' => 'admin@helpdesk.test',
                'role' => 'Admin',
            ],

            [
                'first_name' => 'Agent2',
                'last_name' => 'Support',
                'email' => 'agent2@helpdesk.test',
                'role' => 'Agent',
            ],
            
            [
                'first_name' => 'Agent',
                'last_name' => 'Support',
                'email' => 'agent@helpdesk.test',
                'role' => 'Agent',
            ],
        ];

        foreach ($accounts as $account) {
            $role = Role::where('name', $account['role'])->firstOrFail();

            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'role_id' => $role->id,
                    'password' => Hash::make('ChangeMe123!'),
                    'is_active' => true,
                ]
            );
        }
    }
}
