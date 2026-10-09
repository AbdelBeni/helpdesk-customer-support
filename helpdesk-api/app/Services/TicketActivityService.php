<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Models\User;

class TicketActivityService
{
    public function log(
        Ticket $ticket,
        User $user,
        string $action,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?array $metadata = null
    ): TicketActivityLog {
        return $ticket->activityLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata,
        ]);
    }
}