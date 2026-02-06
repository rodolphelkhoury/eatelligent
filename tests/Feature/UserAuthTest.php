<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration
     */
    public function test_user_registration_success(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJsonPath('user.name', 'John Doe')
            ->assertJsonPath('user.email', 'john@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test user registration with duplicate email
     */
    public function test_user_registration_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    /**
     * Test user registration with missing name
     */
    public function test_user_registration_missing_name(): void
    {
        $response = $this->postJson('/api/user/register', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    /**
     * Test user registration with missing email
     */
    public function test_user_registration_missing_email(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test user registration with missing password
     */
    public function test_user_registration_missing_password(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /**
     * Test user registration with password too short
     */
    public function test_user_registration_password_too_short(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }

    /**
     * Test user registration with non-matching passwords
     */
    public function test_user_registration_password_not_confirmed(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');
    }

    /**
     * Test user registration with invalid email
     */
    public function test_user_registration_invalid_email(): void
    {
        $response = $this->postJson('/api/user/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Test successful user login
     */
    public function test_user_login_success(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'john@example.com');
    }

    /**
     * Test user login with invalid email
     */
    public function test_user_login_invalid_email(): void
    {
        $response = $this->postJson('/api/user/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test user login with wrong password
     */
    public function test_user_login_wrong_password(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test user login with missing email
     */
    public function test_user_login_missing_email(): void
    {
        $response = $this->postJson('/api/user/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test user login with missing password
     */
    public function test_user_login_missing_password(): void
    {
        $response = $this->postJson('/api/user/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    /**
     * Test user login with password too short
     */
    public function test_user_login_password_too_short(): void
    {
        $response = $this->postJson('/api/user/login', [
            'email' => 'john@example.com',
            'password' => 'pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }
}
