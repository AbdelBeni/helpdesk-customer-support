<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketInternalNote extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}