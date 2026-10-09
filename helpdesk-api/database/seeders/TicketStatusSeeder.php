<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        TicketStatus::insert([
            [
                'name' => 'Open',
                'description' => 'Ticket has been created',
                'is_closed' => false,
            ],
            [
                'name' => 'In Progress',
                'description' => 'Ticket is being handled',
                'is_closed' => false,
            ],
            [
                'name' => 'Waiting for Customer',
                'description' => 'Waiting for customer response',
                'is_closed' => false,
            ],
            [
                'name' => 'Resolved',
                'description' => 'Issue has been resolved',
                'is_closed' => false,
            ],
            [
                'name' => 'Closed',
                'description' => 'Ticket has been closed',
                'is_closed' => true,
            ],
        ]);
    }
}