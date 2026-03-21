<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserImageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user, [], 'sanctum-user');
    }

    public function test_attach_image_to_user(): void
    {
        $image = Image::factory()->create([
            'creator_id' => $this->user->id,
            'creator_type' => $this->user->getMorphClass(),
        ]);

        $response = $this->postJson('/api/user/attach-image', [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Image attached successfully')
            ->assertJsonPath('user.id', $this->user->id);

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'owner_id' => $this->user->id,
            'owner_type' => User::class,
        ]);
    }

    public function test_attach_image_fails_if_user_already_has_image(): void
    {
        Image::factory()->create([
            'owner_id' => $this->user->id,
            'owner_type' => User::class,
            'creator_id' => $this->user->id,
            'creator_type' => $this->user->getMorphClass(),
        ]);

        $newImage = Image::factory()->create([
            'creator_id' => $this->user->id,
            'creator_type' => $this->user->getMorphClass(),
        ]);

        $response = $this->postJson('/api/user/attach-image', [
            'image_id' => $newImage->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This user already has a profile image.');

        $this->assertDatabaseMissing('images', [
            'id' => $newImage->id,
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_attach_image_fails_if_image_already_attached(): void
    {
        $otherUser = User::factory()->create();

        $image = Image::factory()->create([
            'creator_id' => $this->user->id,
            'creator_type' => $this->user->getMorphClass(),
            'owner_id' => $otherUser->id,
            'owner_type' => User::class,
        ]);

        $response = $this->postJson('/api/user/attach-image', [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This image is already attached to another owner.');
    }

    public function test_detach_image_from_user(): void
    {
        $image = Image::factory()->create([
            'owner_id' => $this->user->id,
            'owner_type' => User::class,
            'creator_id' => $this->user->id,
            'creator_type' => $this->user->getMorphClass(),
        ]);

        $response = $this->deleteJson('/api/user/detach-image', [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Image detached successfully');

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'owner_id' => null,
            'owner_type' => null,
        ]);
    }

    public function test_detach_image_fails_if_not_belongs_to_user(): void
    {
        $otherUser = User::factory()->create();

        $image = Image::factory()->create([
            'owner_id' => $otherUser->id,
            'owner_type' => User::class,
            'creator_id' => $otherUser->id,
            'creator_type' => $otherUser->getMorphClass(),
        ]);

        $response = $this->deleteJson('/api/user/detach-image', [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Image not found for this user');
    }

    public function test_attach_image_fails_if_image_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser, [], 'sanctum-user');

        $image = Image::factory()->create([
            'owner_id' => null,
            'owner_type' => null,
        ]);

        Sanctum::actingAs($this->user, [], 'sanctum-user');

        $response = $this->postJson('/api/user/attach-image', [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You can only attach images you have created.');

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'owner_id' => null,
        ]);
    }
}
