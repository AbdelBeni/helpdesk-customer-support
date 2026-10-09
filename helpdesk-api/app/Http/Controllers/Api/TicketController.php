<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketActivityService;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Services\TicketStatusService;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::query()
            ->with(['customer', 'category', 'priority', 'status']);

        if ($user->hasRole('Customer')) {
            $query->where('customer_id', $user->id);
        }

        if ($user->hasRole('Agent')) {
            $query->whereHas('assignments', function ($query) use ($user) {
                $query->where('agent_id', $user->id)
                    ->whereNull('unassigned_at');
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('status', function ($query) use ($request) {
                $query->where('name', $request->status);
            });
        }

        if ($request->filled('priority')) {
            $query->whereHas('priority', function ($query) use ($request) {
                $query->where('name', $request->priority);
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($query) use ($request) {
                $query->where('name', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $allowedSorts = [
            'created_at',
            'updated_at',
            'ticket_number',
            'subject',
        ];

        $sort = $request->input('sort', '-created_at');

        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';

        $column = Str::startsWith($sort, '-')
            ? Str::after($sort, '-')
            : $sort;

        if (!in_array($column, $allowedSorts, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }

        $query->orderBy($column, $direction);

        $perPage = min(
            max($request->integer('per_page', 15), 1),
            100
        );

        return TicketResource::collection(
            $query->paginate($perPage)
        );
    }

    public function store(
        StoreTicketRequest $request,
        TicketActivityService $activityService
    ): TicketResource {
        $ticket = DB::transaction(function () use ($request) {
            $openStatus = TicketStatus::where('name', 'Open')->firstOrFail();

            return Ticket::create([
                'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
                'customer_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'priority_id' => $request->priority_id,
                'status_id' => $openStatus->id,
                'subject' => $request->subject,
                'description' => $request->description,
            ]);
        });

        $ticket = $ticket->fresh([
            'customer',
            'category',
            'priority',
            'status',
        ]);

        $activityService->log(
            $ticket,
            $request->user(),
            'ticket_created',
            null,
            [
                'ticket_number' => $ticket->ticket_number,
            ]
        );

        return new TicketResource($ticket);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'customer',
            'category',
            'priority',
            'status',
            'activeAssignment.agent',
        ]);

        return new TicketResource($ticket);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketActivityService $activityService
    ): TicketResource {
        $this->authorize('update', $ticket);

        $oldValues = $ticket->only([
            'category_id',
            'priority_id',
            'subject',
            'description',
        ]);

        $ticket->update($request->validated());

        $newValues = $ticket->fresh()->only([
            'category_id',
            'priority_id',
            'subject',
            'description',
        ]);

        $activityService->log(
            $ticket,
            $request->user(),
            'ticket_updated',
            $oldValues,
            $newValues
        );

        $ticket->load([
            'customer',
            'category',
            'priority',
            'status',
        ]);

        return new TicketResource($ticket);
    }

    public function updateStatus(
        UpdateTicketStatusRequest $request,
        Ticket $ticket,
        TicketStatusService $statusService
    ): TicketResource {
        $updatedTicket = $statusService->change(
            $ticket,
            $request->user(),
            (int) $request->validated('status_id')
        );

        return new TicketResource($updatedTicket);
    }

    public function unassigned(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Agent')
                && $request->user()->hasPermission('tickets.view'),
            403
        );

        $perPage = min(
            max($request->integer('per_page', 15), 1),
            100
        );

        $tickets = Ticket::with([
            'customer',
            'category',
            'priority',
            'status',
        ])
            ->whereHas('status', function ($query) {
                $query->where('name', 'Open');
            })
            ->whereDoesntHave('assignments', function ($query) {
                $query->whereNull('unassigned_at');
            })
            ->latest()
            ->paginate($perPage);

        return TicketResource::collection($tickets);
    }
}