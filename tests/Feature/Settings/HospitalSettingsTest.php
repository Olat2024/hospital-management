<?php

namespace Tests\Feature\Settings;

use App\Models\Hospital;
use App\Models\HospitalSetup;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HospitalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/settings/hospital')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_hospital_setup_screen(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings/hospital')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Hospital')
                ->has('hospital')
                ->where('hospital.name', 'Siloam Grace Clinic & Maternity Home'));
    }

    public function test_hospital_favicon_is_shared_and_used_in_the_application_head(): void
    {
        Storage::fake('public');
        $faviconPath = UploadedFile::fake()->image('hospital-favicon.png')->store('hospitals', 'public');
        HospitalSetup::forHospital(Hospital::default()->id)->update(['favicon' => $faviconPath]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('hospital_favicon_url', Storage::disk('public')->url($faviconPath)))
            ->assertSee(Storage::disk('public')->url($faviconPath));
    }

    public function test_receptionist_cannot_access_hospital_setup(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');

        $this->actingAs($user)->get('/settings/hospital')->assertForbidden();
        $this->actingAs($user)->put('/settings/hospital', ['name' => 'Nope'])->assertForbidden();
    }

    public function test_admin_can_update_hospital_details(): void
    {
        $hospital = Hospital::default();

        $this->actingAs($this->admin())
            ->put('/settings/hospital', [
                'name' => 'Sunrise Medical Centre',
                'address' => '12 Harmony Road, Lagos',
                'phone' => '+234 801 234 5678',
                'email' => 'info@sunrise.test',
                'is_active' => 1,
            ])
            ->assertRedirect('/settings/hospital')
            ->assertSessionHas('success');

        $this->assertSame('Sunrise Medical Centre', $hospital->fresh()->name);
        $this->assertSame('12 Harmony Road, Lagos', $hospital->fresh()->address);
        $this->assertSame('+234 801 234 5678', $hospital->fresh()->phone);
    }

    public function test_admin_can_upload_a_hospital_logo(): void
    {
        Storage::fake('s3');
        config(['filesystems.uploads_disk' => 's3']);

        $hospital = Hospital::default();

        $this->actingAs($this->admin())
            ->put('/settings/hospital', [
                'name' => $hospital->name,
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect('/settings/hospital');

        $hospital->refresh();

        $this->assertNotNull($hospital->logo);
        $this->assertStringStartsWith('hospitals/', $hospital->logo);
        Storage::disk('s3')->assertExists($hospital->logo);
        $this->assertSame(Storage::disk('s3')->url($hospital->logo), $hospital->logo_url);
    }

    public function test_admin_can_upload_hospital_setup_images_with_multipart_method_spoofing(): void
    {
        Storage::fake('public');

        $setup = HospitalSetup::forHospital(Hospital::default()->id);

        $this->actingAs($this->admin())
            ->post('/settings/hospital-setup', [
                '_method' => 'PUT',
                'name' => 'Siloam Grace Clinic & Maternity Home',
                'logo' => UploadedFile::fake()->image('logo.png'),
                'favicon' => UploadedFile::fake()->image('favicon.png'),
            ])
            ->assertRedirect('/settings/hospital-setup')
            ->assertSessionHas('success');

        $setup->refresh();

        $this->assertStringStartsWith('hospitals/', $setup->logo);
        $this->assertStringStartsWith('hospitals/', $setup->favicon);
        Storage::disk('public')->assertExists($setup->logo);
        Storage::disk('public')->assertExists($setup->favicon);
    }

    public function test_hospital_setup_images_use_the_configured_upload_disk(): void
    {
        Storage::fake('s3');
        config(['filesystems.uploads_disk' => 's3']);

        $setup = HospitalSetup::forHospital(Hospital::default()->id);

        $this->actingAs($this->admin())
            ->put('/settings/hospital-setup', [
                'name' => 'Siloam Grace Clinic & Maternity Home',
                'logo' => UploadedFile::fake()->image('logo.png'),
                'favicon' => UploadedFile::fake()->image('favicon.png'),
            ])
            ->assertRedirect('/settings/hospital-setup')
            ->assertSessionHas('success');

        $setup->refresh();

        Storage::disk('s3')->assertExists($setup->logo);
        Storage::disk('s3')->assertExists($setup->favicon);
        $this->assertSame(Storage::disk('s3')->url($setup->logo), $setup->logo_url);
        $this->assertSame(Storage::disk('s3')->url($setup->favicon), $setup->favicon_url);
    }

    public function test_replacing_the_logo_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $hospital = Hospital::default();
        $oldPath = UploadedFile::fake()->image('old.png')->store('hospitals', 'public');
        $hospital->update(['logo' => $oldPath]);

        $this->actingAs($this->admin())
            ->put('/settings/hospital', [
                'name' => $hospital->name,
                'logo' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertRedirect('/settings/hospital');

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($hospital->fresh()->logo);
    }

    public function test_logo_must_be_a_real_image(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put('/settings/hospital', [
                'name' => 'Anything',
                'logo' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('logo');
    }
}
