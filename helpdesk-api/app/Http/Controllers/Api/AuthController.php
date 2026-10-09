<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        EmailVerificationService $verificationService
    ): JsonResponse {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();

        $user = User::create([
            ...$request->validated(),
            'role_id' => $customerRole->id,
        ]);

        $user->load('role');

        $verificationService->sendCode($user);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully. A verification code has been sent to your email.',
            'user' => new UserResource($user),
            'email_verified' => false,
        ], 201);
    }

    public function verifyEmail(
        Request $request,
        EmailVerificationService $verificationService
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification request.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.',
            ], 422);
        }

        $verified = $verificationService->verifyCode(
            $user,
            $validated['code']
        );

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user->load('role');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function resendVerification(
        Request $request,
        EmailVerificationService $verificationService
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resend verification code.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.',
            ], 422);
        }

        $verificationService->sendCode($user);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
        ]);
    }

    public function emailVerificationStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'email_verified' => (bool) $user->email_verified_at,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->validated())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive',
            ], 403);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $user->load('role');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load('role')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function unassigned(Request $request)
    {
        abort_unless(
            $request->user()->hasRole('Agent')
                && $request->user()->hasPermission('tickets.view'),
            403
        );

        $tickets = Ticket::with([
            'customer',
            'category',
            'priority',
            'status',
        ])
            ->whereHas('status', function ($query) {
                $query->where('name', 'Open');
            })
            ->whereDoesntHave('assignments', function ($query) {
                $query->whereNull('unassigned_at');
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TicketResource::collection($tickets);
    }
}