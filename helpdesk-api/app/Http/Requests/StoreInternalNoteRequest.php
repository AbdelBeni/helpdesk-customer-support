<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternalNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('tickets.internal_notes');
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:10000'],
        ];
    }
}