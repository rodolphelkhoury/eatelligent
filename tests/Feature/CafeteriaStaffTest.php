<?php

namespace Tests\Feature;

use App\Models\CafeteriaStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeteriaStaffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful cafeteria staff login
     */
    public function test_cafeteria_staff_login_success(): void
    {
        $staff = CafeteriaStaff::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/cafeteria-staff/login', [
            'email' => 'staff@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cafeteria_staff' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJsonPath('cafeteria_staff.id', $staff->id)
            ->assertJsonPath('cafeteria_staff.email', 'staff@example.com');
    }

    /**
     * Test login with invalid email
     */
    public function test_cafeteria_staff_login_invalid_email(): void
    {
        $response = $this->postJson('/api/cafeteria-staff/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test login with wrong password
     */
    public function test_cafeteria_staff_login_wrong_password(): void
    {
        CafeteriaStaff::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/cafeteria-staff/login', [
            'email' => 'staff@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test login with missing email
     */
    public function test_cafeteria_staff_login_missing_email(): void
    {
        $response = $this->postJson('/api/cafeteria-staff/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test login with missing password
     */
    public function test_cafeteria_staff_login_missing_password(): void
    {
        $response = $this->postJson('/api/cafeteria-staff/login', [
            'email' => 'staff@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /**
     * Test login with password too short
     */
    public function test_cafeteria_staff_login_password_too_short(): void
    {
        $response = $this->postJson('/api/cafeteria-staff/login', [
            'email' => 'staff@example.com',
            'password' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }

    /**
     * Test protected route requires authentication
     */
    public function test_cafeteria_staff_protected_route_requires_auth(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Cafeteria staff not authenticated.');
    }
}
