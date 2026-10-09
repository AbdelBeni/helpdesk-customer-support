<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\ClaimTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;

class TicketAssignmentController extends Controller
{
    public function store(
        AssignTicketRequest $request,
        Ticket $ticket,
        TicketAssignmentService $assignmentService
    ) {
        $agent = User::findOrFail(
            $request->validated('agent_id')
        );

        $assignment = $assignmentService->assign(
            $ticket,
            $agent,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function claim(
        ClaimTicketRequest $request,
        Ticket $ticket,
        TicketAssignmentService $assignmentService
    ) {
        $assignment = $assignmentService->claim(
            $ticket,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Ticket claimed successfully.',
            'data' => $assignment,
        ], 201);
    }
}