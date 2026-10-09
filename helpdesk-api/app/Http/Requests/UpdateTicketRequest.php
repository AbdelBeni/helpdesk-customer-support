<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('tickets.update');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:ticket_categories,id'],
            'priority_id' => ['sometimes', 'integer', 'exists:ticket_priorities,id'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10'],
        ];
    }
}