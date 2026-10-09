<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Admin')
                || $request->user()->hasRole('Agent'),
            403
        );

        $statuses = TicketStatus::pluck('id', 'name');

        $resolvedTickets = Ticket::whereNotNull('resolved_at')
        ->get([
            'created_at',
            'resolved_at',
        ]);

    $averageResolution = $resolvedTickets->count()
        ? $resolvedTickets->avg(function ($ticket) {
            return $ticket->created_at->diffInMinutes(
                $ticket->resolved_at
            );
        })
        : null;

        return response()->json([
            'total_tickets' => Ticket::count(),

            'open_tickets' => Ticket::where(
                'status_id',
                $statuses['Open']
            )->count(),

            'in_progress_tickets' => Ticket::where(
                'status_id',
                $statuses['In Progress']
            )->count(),

            'waiting_for_customer_tickets' => Ticket::where(
                'status_id',
                $statuses['Waiting for Customer']
            )->count(),

            'resolved_tickets' => Ticket::where(
                'status_id',
                $statuses['Resolved']
            )->count(),

            'closed_tickets' => Ticket::where(
                'status_id',
                $statuses['Closed']
            )->count(),

            'unassigned_tickets' => Ticket::where(
                'status_id',
                $statuses['Open']
            )
                ->whereDoesntHave('assignments', function ($query) {
                    $query->whereNull('unassigned_at');
                })
                ->count(),

            'tickets_by_priority' => TicketPriority::withCount('tickets')
                ->orderBy('level')
                ->get()
                ->map(function ($priority) {
                    return [
                        'id' => $priority->id,
                        'name' => $priority->name,
                        'level' => $priority->level,
                        'count' => $priority->tickets_count,
                    ];
                })
                ->values(),

            'tickets_by_category' => TicketCategory::withCount('tickets')
                ->orderBy('name')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'count' => $category->tickets_count,
                    ];
                })
                ->values(),

            'average_resolution_time' => [
                'minutes' => $averageResolution !== null
                    ? round((float) $averageResolution)
                    : null,

                'hours' => $averageResolution !== null
                    ? round((float) $averageResolution / 60, 2)
                    : null,
            ],
        ]);
    }
    
    public function ticketTrends(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Admin')
                || $request->user()->hasRole('Agent'),
            403
        );

        $period = $request->integer('period', 7);

        abort_unless(
            in_array($period, [7, 30]),
            422,
            'Period must be 7 or 30.'
        );

        $startDate = now()->subDays($period - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $tickets = Ticket::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->orWhereBetween('resolved_at', [$startDate, $endDate])
                ->orWhereBetween('closed_at', [$startDate, $endDate]);
        })->get([
            'created_at',
            'resolved_at',
            'closed_at',
        ]);

        $trends = collect();

        for ($i = 0; $i < $period; $i++) {
            $date = $startDate->copy()->addDays($i);

            $created = $tickets->filter(function ($ticket) use ($date) {
                return $ticket->created_at->isSameDay($date);
            })->count();

            $resolved = $tickets->filter(function ($ticket) use ($date) {
                return $ticket->resolved_at
                    && $ticket->resolved_at->isSameDay($date);
            })->count();

            $closed = $tickets->filter(function ($ticket) use ($date) {
                return $ticket->closed_at
                    && $ticket->closed_at->isSameDay($date);
            })->count();

            $trends->push([
                'date' => $date->toDateString(),
                'created' => $created,
                'resolved' => $resolved,
                'closed' => $closed,
            ]);
        }

        return response()->json([
            'period' => $period,
            'from' => $startDate->toDateString(),
            'to' => now()->toDateString(),
            'data' => $trends,
        ]);
    }

    public function agentPerformance(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Admin')
                || $request->user()->hasRole('Agent'),
            403
        );

        $agents = \App\Models\User::whereHas('role', function ($query) {
            $query->where('name', 'Agent');
        })
            ->where('is_active', true)
            ->with('assignments')
            ->get();

        $data = $agents->map(function ($agent) {
            $assignments = $agent->assignments;

            $handledTicketIds = $assignments
                ->pluck('ticket_id')
                ->unique();

            $currentTicketIds = $assignments
                ->whereNull('unassigned_at')
                ->pluck('ticket_id')
                ->unique();

            $handledTickets = Ticket::whereIn(
                'id',
                $handledTicketIds
            )
                ->with('status')
                ->get([
                    'id',
                    'status_id',
                    'created_at',
                    'resolved_at',
                ]);

            $currentTickets = $handledTickets->whereIn(
                'id',
                $currentTicketIds
            );

            $resolvedTickets = $handledTickets->filter(
                fn ($ticket) => $ticket->resolved_at !== null
            );

            $averageResolution = $resolvedTickets->count()
                ? $resolvedTickets->avg(function ($ticket) {
                    return $ticket->created_at->diffInMinutes(
                        $ticket->resolved_at
                    );
                })
                : null;

            $inProgress = $currentTickets->filter(
                fn ($ticket) => $ticket->status?->name === 'In Progress'
            )->count();

            $waitingForCustomer = $currentTickets->filter(
                fn ($ticket) => $ticket->status?->name === 'Waiting for Customer'
            )->count();

            $resolved = $handledTickets->filter(
                fn ($ticket) => $ticket->status?->name === 'Resolved'
            )->count();

            $closed = $handledTickets->filter(
                fn ($ticket) => $ticket->status?->name === 'Closed'
            )->count();

            return [
                'agent' => [
                    'id' => $agent->id,
                    'name' => trim(
                        $agent->first_name . ' ' . $agent->last_name
                    ),
                    'email' => $agent->email,
                ],

                'current_assigned_tickets' => $currentTickets->count(),
                'handled_tickets' => $handledTickets->count(),
                'in_progress_tickets' => $inProgress,
                'waiting_for_customer_tickets' => $waitingForCustomer,
                'resolved_tickets' => $resolved,
                'closed_tickets' => $closed,

                'average_resolution_time' => [
                    'minutes' => $averageResolution !== null
                        ? round($averageResolution)
                        : null,

                    'hours' => $averageResolution !== null
                        ? round($averageResolution / 60, 2)
                        : null,
                ],
            ];
        });

        return response()->json([
            'data' => $data->values(),
        ]);
    }

    public function responsePerformance(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Admin')
                || $request->user()->hasRole('Agent'),
            403
        );

        $tickets = Ticket::query()
            ->whereNotNull('first_response_at')
            ->get([
                'id',
                'created_at',
                'first_response_at',
            ]);

        $respondedTickets = $tickets->count();

        $averageResponse = $respondedTickets
            ? $tickets->avg(function ($ticket) {
                return $ticket->created_at->diffInMinutes(
                    $ticket->first_response_at
                );
            })
            : null;

        $totalTickets = Ticket::count();

        $ticketsWithoutResponse = Ticket::whereNull(
            'first_response_at'
        )->count();

        $responseRate = $totalTickets > 0
            ? round(($respondedTickets / $totalTickets) * 100, 2)
            : 0;

        return response()->json([
            'total_tickets' => $totalTickets,

            'responded_tickets' => $respondedTickets,

            'tickets_without_response' => $ticketsWithoutResponse,

            'response_rate' => $responseRate,

            'average_first_response_time' => [
                'minutes' => $averageResponse !== null
                    ? round($averageResponse)
                    : null,

                'hours' => $averageResponse !== null
                    ? round($averageResponse / 60, 2)
                    : null,
            ],
        ]);
    }

    public function customerStats(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Customer'),
            403
        );

        $customerId = $request->user()->id;

        $statuses = TicketStatus::pluck('id', 'name');

        $tickets = Ticket::where('customer_id', $customerId);

        $resolvedTickets = (clone $tickets)
            ->whereNotNull('resolved_at')
            ->get([
                'created_at',
                'resolved_at',
            ]);

        $averageResolution = $resolvedTickets->count()
            ? $resolvedTickets->avg(function ($ticket) {
                return $ticket->created_at->diffInMinutes(
                    $ticket->resolved_at
                );
            })
            : null;

        return response()->json([
            'total_tickets' => (clone $tickets)->count(),

            'open_tickets' => (clone $tickets)
                ->where('status_id', $statuses['Open'])
                ->count(),

            'in_progress_tickets' => (clone $tickets)
                ->where('status_id', $statuses['In Progress'])
                ->count(),

            'waiting_for_customer_tickets' => (clone $tickets)
                ->where('status_id', $statuses['Waiting for Customer'])
                ->count(),

            'resolved_tickets' => (clone $tickets)
                ->where('status_id', $statuses['Resolved'])
                ->count(),

            'closed_tickets' => (clone $tickets)
                ->where('status_id', $statuses['Closed'])
                ->count(),

            'average_resolution_time' => [
                'minutes' => $averageResolution !== null
                    ? round((float) $averageResolution)
                    : null,

                'hours' => $averageResolution !== null
                    ? round((float) $averageResolution / 60, 2)
                    : null,
            ],
        ]);
    }


}