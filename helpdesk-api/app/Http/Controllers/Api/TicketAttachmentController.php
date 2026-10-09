<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketAttachmentRequest;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Ticket;
use App\Services\TicketActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $attachments = $ticket->attachments()
            ->with('uploader')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return TicketAttachmentResource::collection($attachments);
    }

    public function store(
        StoreTicketAttachmentRequest $request,
        Ticket $ticket,
        TicketActivityService $activityService
    ): TicketAttachmentResource {
        $this->authorize('view', $ticket);

        if ($request->filled('message_id')) {
            $messageExists = $ticket->messages()
                ->whereKey($request->message_id)
                ->exists();

            abort_unless(
                $messageExists,
                422,
                'The message does not belong to this ticket.'
            );
        }

        $file = $request->file('file');

        $path = $file->store(
            "tickets/{$ticket->id}/attachments",
            'public'
        );

        $attachment = $ticket->attachments()->create([
            'message_id' => $request->message_id,
            'uploaded_by' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => Storage::disk('public')->url($path),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $activityService->log(
            $ticket,
            $request->user(),
            'attachment_uploaded',
            null,
            [
                'attachment_id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
            ]
        );

        $attachment->load('uploader');

        return new TicketAttachmentResource($attachment);
    }

    public function destroy(
        Request $request,
        Ticket $ticket,
        int $attachment
    ) {
        $this->authorize('update', $ticket);

        $attachment = $ticket->attachments()->findOrFail($attachment);

        $path = str_replace(
            Storage::disk('public')->url(''),
            '',
            $attachment->file_path
        );

        Storage::disk('public')->delete($path);

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}
