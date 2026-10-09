<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        TicketPriority::insert([
            ['name' => 'Low', 'level' => 1],
            ['name' => 'Medium', 'level' => 2],
            ['name' => 'High', 'level' => 3],
            ['name' => 'Urgent', 'level' => 4],
        ]);
    }
}