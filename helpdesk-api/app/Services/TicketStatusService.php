<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class TicketStatusService
{
    private const TRANSITIONS = [
        'Open' => ['In Progress'],
        'In Progress' => ['Waiting for Customer', 'Resolved'],
        'Waiting for Customer' => ['In Progress'],
        'Resolved' => ['Closed', 'Open'],
        'Closed' => ['Open'],
    ];

    public function change(
        Ticket $ticket,
        User $user,
        int $statusId
    ): Ticket {
        if (!$user->hasPermission('tickets.update')) {
            throw new AuthorizationException(
                'You do not have permission to change ticket status.'
            );
        }

        if (!$user->hasRole('Admin')) {
            $assigned = $ticket->assignments()
                ->where('agent_id', $user->id)
                ->whereNull('unassigned_at')
                ->exists();

            if (!$user->hasRole('Agent') || !$assigned) {
                throw new AuthorizationException(
                    'You cannot change this ticket status.'
                );
            }
        }

        return DB::transaction(function () use (
            $ticket,
            $user,
            $statusId
        ) {
            $ticket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->with('status', 'customer')
                ->firstOrFail();

            $newStatus = TicketStatus::findOrFail($statusId);
            $currentStatus = $ticket->status->name;

            if (!in_array(
                $newStatus->name,
                self::TRANSITIONS[$currentStatus] ?? [],
                true
            )) {
                throw ValidationException::withMessages([
                    'status_id' => [
                        "Transition from {$currentStatus} to {$newStatus->name} is not allowed.",
                    ],
                ]);
            }

            $ticket->status_id = $newStatus->id;

            if ($newStatus->name === 'Resolved') {
                $ticket->resolved_at = now();
            }

            if ($newStatus->name === 'Closed') {
                $ticket->closed_at = now();
            }

            if ($newStatus->name === 'Open') {
                $ticket->resolved_at = null;
                $ticket->closed_at = null;
            }

            $ticket->save();

            app(TicketActivityService::class)->log(
                $ticket,
                $user,
                'status_changed',
                ['status' => $currentStatus],
                ['status' => $newStatus->name]
            );

            $notificationType = match ($newStatus->name) {
                'Resolved' => 'ticket_resolved',
                'Open' => 'ticket_reopened',
                default => 'ticket_status_changed',
            };

            $notificationTitle = match ($newStatus->name) {
                'Resolved' => 'Ticket resolved',
                'Open' => 'Ticket reopened',
                default => 'Ticket status updated',
            };

            $notificationMessage = match ($newStatus->name) {
                'Resolved' =>
                    "Ticket {$ticket->ticket_number} has been resolved.",

                'Open' =>
                    "Ticket {$ticket->ticket_number} has been reopened.",

                default =>
                    "Ticket {$ticket->ticket_number} status changed from {$currentStatus} to {$newStatus->name}.",
            };

            app(NotificationService::class)->send(
                $ticket->customer,
                $notificationType,
                $notificationTitle,
                $notificationMessage,
                [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus->name,
                ]
            );

            return $ticket->fresh([
                'customer',
                'category',
                'priority',
                'status',
            ]);
        });
    }
}