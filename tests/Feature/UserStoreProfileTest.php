<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserStoreProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, [], 'sanctum-user');
    }

    private function validPayload(): array
    {
        return [
            'weight_kg' => 75.0,
            'height_cm' => 175.0,
            'age' => 22,
            'gender' => 'male',
            'activity_level' => 'moderate',
            'goal' => 'gain_muscle',
        ];
    }

    public function test_store_profile_success(): void
    {
        $response = $this->postJson('/api/user/body-composition', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'weight_kg',
                'height_cm',
                'age',
                'gender',
                'activity_level',
                'goal',
                'bmi',
                'bmr',
                'tdee',
                'daily_calories',
                'daily_protein_g',
                'daily_carbs_g',
                'daily_fat_g',
            ]);

        $profile = $response->json();

        $this->assertEquals($this->user->id, $profile['user_id']);
        $this->assertEquals('gain_muscle', $profile['goal']);
        $this->assertEquals('moderate', $profile['activity_level']);

        $this->assertGreaterThan(0, $profile['bmi']);
        $this->assertGreaterThan(0, $profile['bmr']);
        $this->assertGreaterThan(0, $profile['tdee']);
        $this->assertGreaterThan(0, $profile['daily_calories']);
        $this->assertGreaterThan(0, $profile['daily_protein_g']);
        $this->assertGreaterThan(0, $profile['daily_carbs_g']);
        $this->assertGreaterThan(0, $profile['daily_fat_g']);

        $this->assertDatabaseHas('body_compositions', [
            'user_id' => $this->user->id,
            'weight_kg' => 75.0,
            'height_cm' => 175.0,
            'age' => 22,
            'gender' => 'male',
            'activity_level' => 'moderate',
            'goal' => 'gain_muscle',
        ]);
    }

    public function test_store_profile_updates_existing_instead_of_creating_duplicate(): void
    {
        $this->postJson('/api/user/body-composition', $this->validPayload());

        $updated = array_merge($this->validPayload(), [
            'weight_kg' => 80.0,
            'goal' => 'maintain',
        ]);

        $response = $this->postJson('/api/user/body-composition', $updated);

        $response->assertStatus(201);

        $this->assertDatabaseCount('body_compositions', 1);

        $this->assertDatabaseHas('body_compositions', [
            'user_id' => $this->user->id,
            'weight_kg' => 80.0,
            'goal' => 'maintain',
        ]);
    }

    public function test_store_profile_validation_error(): void
    {
        $response = $this->postJson('/api/user/body-composition', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_store_profile_invalid_gender(): void
    {
        $payload = array_merge($this->validPayload(), ['gender' => 'other']);

        $response = $this->postJson('/api/user/body-composition', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.gender', fn ($v) => count($v) > 0);
    }

    public function test_store_profile_invalid_activity_level(): void
    {
        $payload = array_merge($this->validPayload(), ['activity_level' => 'couch_potato']);

        $response = $this->postJson('/api/user/body-composition', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.activity_level', fn ($v) => count($v) > 0);
    }

    public function test_store_profile_invalid_goal(): void
    {
        $payload = array_merge($this->validPayload(), ['goal' => 'become_superhero']);

        $response = $this->postJson('/api/user/body-composition', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.goal', fn ($v) => count($v) > 0);
    }

    public function test_store_profile_female_computes_correctly(): void
    {
        $payload = array_merge($this->validPayload(), [
            'gender' => 'female',
            'goal' => 'lose_weight',
        ]);

        $response = $this->postJson('/api/user/body-composition', $payload);

        $response->assertStatus(201);

        $profile = $response->json();

        $this->assertGreaterThan(0, $profile['daily_calories']);

        $this->assertLessThan($profile['tdee'], $profile['daily_calories']);

        $this->assertDatabaseHas('body_compositions', [
            'user_id' => $this->user->id,
            'gender' => 'female',
            'goal' => 'lose_weight',
        ]);
    }

    public function test_store_profile_gain_muscle_calories_above_tdee(): void
    {
        $response = $this->postJson('/api/user/body-composition', $this->validPayload());

        $response->assertStatus(201);

        $profile = $response->json();

        $this->assertGreaterThan($profile['tdee'], $profile['daily_calories']);
    }

    public function test_store_profile_maintain_calories_equal_tdee(): void
    {
        $payload = array_merge($this->validPayload(), ['goal' => 'maintain']);

        $response = $this->postJson('/api/user/body-composition', $payload);

        $response->assertStatus(201);

        $profile = $response->json();

        $this->assertEquals((int) round($profile['tdee']), $profile['daily_calories']);
    }

    public function test_store_profile_unauthenticated(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/user/body-composition', $this->validPayload());

        $response->assertStatus(401);
    }
}
