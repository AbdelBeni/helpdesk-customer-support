<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('tickets.assign');
    }

    public function rules(): array
    {
        return [
            'agent_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}