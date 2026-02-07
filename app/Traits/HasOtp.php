<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasOtp
{
    /**
     * Get all OTPs for this model.
     */
    public function otps(): MorphMany
    {
        return $this->morphMany(\App\Models\Otp::class, 'otpable');
    }

    /**
     * Generate and save a new OTP.
     */
    public function generateOtp(int $expiresInMinutes = 5): string
    {
        $otp = rand(100000, 999999);

        $this->otps()->create([
            'otp' => $otp,
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return (string) $otp;
    }

    /**
     * Verify an OTP.
     */
    public function verifyOtp(string $otp): bool
    {
        $otpRecord = $this->otps()
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->first();

        if ($otpRecord) {
            $otpRecord->delete();

            return true;
        }

        return false;
    }
}
