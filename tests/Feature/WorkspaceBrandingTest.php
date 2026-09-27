<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BrandColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkspaceBrandingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
    }

    public function test_owners_rename_recolour_and_add_a_logo(): void
    {
        $this->actingAs($this->owner)->get('/t/acme/settings')->assertOk()->assertSee('Settings &amp; branding', false)->assertDontSee('&amp;amp;', false);

        $this->actingAs($this->owner)->put('/t/acme/settings', [
            'name' => 'Acme Academy',
            'brand_color' => '#0E7490',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect(route('tenant.settings.edit', ['tenant' => 'acme']));

        $this->tenant->refresh();
        $this->assertSame('Acme Academy', $this->tenant->name);
        $this->assertSame('#0e7490', $this->tenant->brand_color);
        Storage::disk('public')->assertExists($this->tenant->logo_path);

        $learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($learner)->get('/t/acme')
            ->assertOk()
            ->assertSee('--color-brand-600: #0e7490;', false)
            ->assertSee($this->tenant->logoUrl(), false)
            ->assertSee('Acme Academy');

        // The sign-in page carries the branding too.
        auth()->logout();
        $this->get('/t/acme/login')->assertSee('--color-brand-600: #0e7490;', false)->assertSee($this->tenant->logoUrl(), false);
    }

    public function test_colours_too_light_for_white_text_are_refused(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/settings', ['name' => 'Acme', 'brand_color' => '#fde047'])
            ->assertSessionHasErrors('brand_color');
        $this->actingAs($this->owner)->put('/t/acme/settings', ['name' => 'Acme', 'brand_color' => 'red; }'])
            ->assertSessionHasErrors('brand_color');

        $this->assertNull($this->tenant->fresh()->brand_color);
        foreach (array_keys(BrandColor::PRESETS) as $preset) {
            $this->assertGreaterThanOrEqual(BrandColor::MIN_CONTRAST, BrandColor::contrastWithWhite($preset), $preset);
        }
    }

    public function test_svg_logos_are_refused_and_logos_can_be_removed(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/settings', [
            'name' => 'Acme', 'logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($this->owner)->put('/t/acme/settings', ['name' => 'Acme', 'logo' => UploadedFile::fake()->image('logo.png')]);
        $path = $this->tenant->fresh()->logo_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->owner)->put('/t/acme/settings', ['name' => 'Acme', 'remove_logo' => 1]);
        $this->assertNull($this->tenant->fresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_branding_stays_in_its_own_workspace(): void
    {
        $this->tenant->update(['brand_color' => '#be123c']);
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $other->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        $this->actingAs(User::factory()->create(['tenant_id' => $other->id]))->get('/t/other')
            ->assertOk()->assertDontSee('#be123c', false);
    }

    public function test_members_cannot_change_settings(): void
    {
        $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($member)->get('/t/acme/settings')->assertForbidden();
        $this->actingAs($member)->put('/t/acme/settings', ['name' => 'Hijacked'])->assertForbidden();
        $this->assertSame('Acme', $this->tenant->fresh()->name);
    }
}
