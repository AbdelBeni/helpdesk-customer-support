<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function sendCode(User $user): void
    {
        $user->emailVerificationCodes()->delete();

        $code = (string) random_int(100000, 999999);

        $verificationCode = $user->emailVerificationCodes()->create([
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        Mail::to($user->email)->send(
            new EmailVerificationCodeMail($user, $code)
        );
    }

    public function verifyCode(User $user, string $code): bool
    {
        $verificationCode = $user->emailVerificationCodes()
            ->latest()
            ->first();

        if (!$verificationCode) {
            return false;
        }

        if ($verificationCode->expires_at->isPast()) {
            $verificationCode->delete();

            return false;
        }

        if ($verificationCode->attempts >= 5) {
            return false;
        }

        $verificationCode->increment('attempts');

        if (!Hash::check($code, $verificationCode->code)) {
            return false;
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        $user->emailVerificationCodes()->delete();

        return true;
    }
}