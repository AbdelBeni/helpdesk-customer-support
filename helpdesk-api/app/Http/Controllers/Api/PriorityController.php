<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketPriority;

class PriorityController extends Controller
{
    public function index()
    {
        $priorities = TicketPriority::query()
            ->orderBy('level')
            ->get([
                'id',
                'name',
                'level',
            ]);

        return response()->json([
            'data' => $priorities,
        ]);
    }
}