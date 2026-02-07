<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerifyUserEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful email verification with valid OTP
     */
    public function test_user_can_verify_email_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $otp = $user->generateOtp();

        Sanctum::actingAs($user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/verify-email', [
            'otp' => $otp,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Email verified successfully.');

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );
    }

    /**
     * Test email verification with invalid OTP
     */
    public function test_user_cannot_verify_email_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user->generateOtp();

        Sanctum::actingAs($user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/verify-email', [
            'otp' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid or expired OTP.');

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    /**
     * Test email verification with expired OTP
     */
    public function test_user_cannot_verify_email_with_expired_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $otp = $user->otps()->create([
            'otp' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/verify-email', [
            'otp' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid or expired OTP.');

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    /**
     * Test email verification without authentication
     */
    public function test_email_verification_requires_authentication(): void
    {
        $response = $this->postJson('/api/user/verify-email', [
            'otp' => '123456',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'User not authenticated.');
    }

    /**
     * Test email verification with missing OTP
     */
    public function test_email_verification_missing_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/verify-email');

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.otp.0', 'The otp field is required.');
    }

    /**
     * Test already verified email
     */
    public function test_user_cannot_verify_email_twice(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/verify-email', [
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Email already verified.');
    }
}
