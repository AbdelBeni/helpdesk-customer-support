<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Customer')) {
            return $ticket->customer_id === $user->id;
        }
        
        if ($user->hasRole('Agent')) {
            return $ticket->assignments()
                ->where('agent_id', $user->id)
                ->whereNull('unassigned_at')
                ->exists();
        }

        return false;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (!$user->hasPermission('tickets.update')) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->hasRole('Agent') &&
            $ticket->assignments()
                ->where('agent_id', $user->id)
                ->whereNull('unassigned_at')
                ->exists();
    }
}