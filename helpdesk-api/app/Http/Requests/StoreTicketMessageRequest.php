<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('tickets.view');
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:10000'],
        ];
    }
}