<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = TicketCategory::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return response()->json([
            'data' => $categories,
        ]);
    }
}