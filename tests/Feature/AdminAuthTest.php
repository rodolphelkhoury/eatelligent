<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful admin registration
     */
    public function test_admin_registration_success(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'admin' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJsonPath('admin.name', 'Admin User')
            ->assertJsonPath('admin.email', 'admin@example.com');

        $this->assertDatabaseHas('admins', [
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);
    }

    /**
     * Test admin registration with duplicate email
     */
    public function test_admin_registration_duplicate_email(): void
    {
        Admin::create([
            'name' => 'Existing Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    /**
     * Test admin registration with missing name
     */
    public function test_admin_registration_missing_name(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    /**
     * Test admin registration with missing email
     */
    public function test_admin_registration_missing_email(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test admin registration with missing password
     */
    public function test_admin_registration_missing_password(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /**
     * Test admin registration with password too short
     */
    public function test_admin_registration_password_too_short(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }

    /**
     * Test admin registration with non-matching passwords
     */
    public function test_admin_registration_password_not_confirmed(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');
    }

    /**
     * Test admin registration with invalid email
     */
    public function test_admin_registration_invalid_email(): void
    {
        $response = $this->postJson('/api/admin/register', [
            'name' => 'Admin User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Test successful admin login
     */
    public function test_admin_login_success(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'admin' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJsonPath('admin.id', $admin->id)
            ->assertJsonPath('admin.email', 'admin@example.com');
    }

    /**
     * Test admin login with invalid email
     */
    public function test_admin_login_invalid_email(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test admin login with wrong password
     */
    public function test_admin_login_wrong_password(): void
    {
        Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test admin login with missing email
     */
    public function test_admin_login_missing_email(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test admin login with missing password
     */
    public function test_admin_login_missing_password(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /**
     * Test admin login with password too short
     */
    public function test_admin_login_password_too_short(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }
}
