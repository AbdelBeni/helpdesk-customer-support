<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketActivityLogResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketActivityLogController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('Admin') ||
            (
                $user->hasRole('Agent') &&
                $ticket->assignments()
                    ->where('agent_id', $user->id)
                    ->whereNull('unassigned_at')
                    ->exists()
            ),
            403
        );

        $logs = $ticket->activityLogs()
            ->with('user')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return TicketActivityLogResource::collection($logs);
    }
}