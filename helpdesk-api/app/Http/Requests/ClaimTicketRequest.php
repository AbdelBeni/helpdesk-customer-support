<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('Agent')
            && $this->user()->hasPermission('tickets.view');
    }

    public function rules(): array
    {
        return [];
    }
}