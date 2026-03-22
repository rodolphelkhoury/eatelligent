<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CafeteriaStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCafeteriaStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->create();

        Sanctum::actingAs($admin, [], 'sanctum-admin');
    }

    /**
     * Test create cafeteria staff success
     */
    public function test_create_cafeteria_staff_success(): void
    {
        $response = $this->postJson('/api/create-cafeteria-staff', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Cafeteria staff created successfully')
            ->assertJsonPath('staff.email', 'john.doe@example.com');

        $this->assertDatabaseHas('cafeteria_staff', [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test create cafeteria staff duplicate email
     */
    public function test_create_cafeteria_staff_duplicate_email(): void
    {
        CafeteriaStaff::factory()->create([
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->postJson('/api/create-cafeteria-staff', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    /**
     * Test create cafeteria staff missing fields
     */
    public function test_create_cafeteria_staff_missing_fields(): void
    {
        $response = $this->postJson('/api/create-cafeteria-staff', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    /**
     * Test update cafeteria staff password success
     */
    public function test_update_cafeteria_staff_password_success(): void
    {
        $staff = CafeteriaStaff::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->putJson('/api/update-cafeteria-staff-password', [
            'email' => 'john.doe@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password updated successfully');

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check(
                'newpassword123',
                $staff->fresh()->password
            )
        );
    }

    /**
     * Test update password with non-existing email
     */
    public function test_update_cafeteria_staff_password_invalid_email(): void
    {
        $response = $this->putJson('/api/update-cafeteria-staff-password', [
            'email' => 'notfound@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Test update password missing fields
     */
    public function test_update_cafeteria_staff_password_missing_fields(): void
    {
        $response = $this->putJson('/api/update-cafeteria-staff-password', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    /**
     * Test update password confirmation mismatch
     */
    public function test_update_cafeteria_staff_password_not_confirmed(): void
    {
        $staff = CafeteriaStaff::factory()->create([
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->putJson('/api/update-cafeteria-staff-password', [
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');
    }
}
