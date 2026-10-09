<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'description' => $this->description,

            'customer' => new UserResource(
                $this->whenLoaded('customer')
            ),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),

            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'id' => $this->priority->id,
                    'name' => $this->priority->name,
                    'level' => $this->priority->level,
                ];
            }),

            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'is_closed' => $this->status->is_closed,
                ];
            }),

            'assignment' => $this->whenLoaded(
                'activeAssignment',
                function () {
                    if (!$this->activeAssignment) {
                        return null;
                    }

                    return [
                        'id' => $this->activeAssignment->id,
                        'agent' => new UserResource(
                            $this->activeAssignment->agent
                        ),
                        'assigned_at' => $this->activeAssignment->assigned_at,
                    ];
                }
            ),

            'created_at' => $this->created_at,
            'first_response_at' => $this->first_response_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
        ];
    }
}