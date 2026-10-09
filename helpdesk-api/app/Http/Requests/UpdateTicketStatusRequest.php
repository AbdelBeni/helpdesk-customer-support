<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('tickets.update');
    }

    public function rules(): array
    {
        return [
            'status_id' => [
                'required',
                'integer',
                'exists:ticket_statuses,id',
            ],
        ];
    }
}