<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_guest_cannot_upload_a_photo(): void
    {
        $this->patch('/profile', ['name' => 'Guest', 'email' => 'guest@test.local'])
            ->assertRedirect('/login');
    }

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile', [
                '_method' => 'PATCH',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('me.png'),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertStringContainsString($user->avatar, $user->avatar_url);
    }

    public function test_profile_photo_uses_the_configured_upload_disk(): void
    {
        Storage::fake('s3');
        config(['filesystems.uploads_disk' => 's3']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('me.png'),
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        Storage::disk('s3')->assertExists($user->avatar);
        $this->assertSame(Storage::disk('s3')->url($user->avatar), $user->avatar_url);
    }

    public function test_replacing_the_photo_deletes_the_previous_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.png')->store('avatars', 'public');
        $user->update(['avatar' => $oldPath]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertRedirect('/profile');

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_updating_details_without_a_photo_keeps_the_current_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('keep.png')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Renamed Person',
                'email' => $user->email,
            ])
            ->assertRedirect('/profile');

        $this->assertSame($path, $user->fresh()->avatar);
        Storage::disk('public')->assertExists($path);
    }

    public function test_photo_must_be_an_image_under_two_megabytes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
            ])
            ->assertSessionHasErrors('avatar');
    }

    public function test_avatar_url_is_null_when_no_photo_is_set(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->fresh()->avatar_url);
    }
}
