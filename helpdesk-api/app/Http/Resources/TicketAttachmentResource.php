<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'url' => $this->file_path,
            'uploaded_by' => new UserResource(
                $this->whenLoaded('uploader')
            ),
            'created_at' => $this->created_at,
        ];
    }
}