<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\Api\TicketAttachmentController;
use App\Http\Controllers\Api\TicketActivityLogController;
use App\Http\Controllers\Api\TicketAssignmentController;
use App\Http\Controllers\Api\TicketInternalNoteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PriorityController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post(
        '/register',
        [AuthController::class, 'register']
    )->middleware('throttle:register');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->middleware('throttle:login');

    Route::post(
        '/verify-email',
        [AuthController::class, 'verifyEmail']
    );

    Route::post(
        '/resend-verification',
        [AuthController::class, 'resendVerification']
    )->middleware('throttle:verification');

    Route::get(
        '/email-verification-status',
        [AuthController::class, 'emailVerificationStatus']
    );

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware([
    'auth:sanctum',
    'throttle:api',
])->group(function () {

    Route::get(
        '/tickets/unassigned',
        [TicketController::class, 'unassigned']
    );

    Route::get(
        '/tickets/{ticket}/internal-notes',
        [TicketInternalNoteController::class, 'index']
    );

    Route::post(
        '/tickets/{ticket}/internal-notes',
        [TicketInternalNoteController::class, 'store']
    );

    Route::apiResource('tickets', TicketController::class)
        ->only(['index', 'store', 'show', 'update']);

    Route::get(
        '/tickets/{ticket}/messages',
        [TicketMessageController::class, 'index']
    );

    Route::post(
        '/tickets/{ticket}/messages',
        [TicketMessageController::class, 'store']
    );

    Route::get(
        '/tickets/{ticket}/attachments',
        [TicketAttachmentController::class, 'index']
    );

    Route::post(
        '/tickets/{ticket}/attachments',
        [TicketAttachmentController::class, 'store']
    );

    Route::delete(
        '/tickets/{ticket}/attachments/{attachment}',
        [TicketAttachmentController::class, 'destroy']
    );

    Route::get(
        '/tickets/{ticket}/activity-logs',
        [TicketActivityLogController::class, 'index']
    );

    Route::patch(
        '/tickets/{ticket}/status',
        [TicketController::class, 'updateStatus']
    );

    Route::post(
        '/tickets/{ticket}/assign',
        [TicketAssignmentController::class, 'store']
    );

    Route::post(
        '/tickets/{ticket}/claim',
        [TicketAssignmentController::class, 'claim']
    );

    Route::get(
        '/notifications',
        [NotificationController::class, 'index']
    );

    Route::get(
        '/notifications/unread-count',
        [NotificationController::class, 'unreadCount']
    );

    Route::patch(
        '/notifications/{notification}/read',
        [NotificationController::class, 'markAsRead']
    );

    Route::get(
        '/dashboard/stats',
        [DashboardController::class, 'stats']
    );

    Route::get('/dashboard/customer-stats', [
        DashboardController::class,
        'customerStats',
    ]);

    Route::get(
        '/dashboard/ticket-trends',
        [DashboardController::class, 'ticketTrends']
    );

    Route::get(
        '/dashboard/agent-performance',
        [DashboardController::class, 'agentPerformance']
    );

    Route::get(
        '/dashboard/response-performance',
        [DashboardController::class, 'responsePerformance']
    );

    Route::get(
        '/agents',
        [AgentController::class, 'index']
    );

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/priorities', [PriorityController::class, 'index']);
});