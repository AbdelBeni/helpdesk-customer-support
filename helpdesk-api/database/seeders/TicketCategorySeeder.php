<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        TicketCategory::insert([
            [
                'name' => 'Technical Support',
                'description' => 'Technical issues and problems',
                'is_active' => true,
            ],
            [
                'name' => 'Billing',
                'description' => 'Invoices and payment issues',
                'is_active' => true,
            ],
            [
                'name' => 'Authentication',
                'description' => 'Login and account access issues',
                'is_active' => true,
            ],
            [
                'name' => 'Bug Report',
                'description' => 'Application bugs and errors',
                'is_active' => true,
            ],
            [
                'name' => 'Feature Request',
                'description' => 'New feature requests',
                'is_active' => true,
            ],
            [
                'name' => 'Other',
                'description' => 'Other support requests',
                'is_active' => true,
            ],
        ]);
    }
}