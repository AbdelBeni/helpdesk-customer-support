<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->role?->name === 'Admin',
            403
        );

        $agents = User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'Agent');
            })
            ->with('role')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return UserResource::collection($agents);
    }
}