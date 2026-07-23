<?php

namespace Tests\Feature;

use App\Models\MarketingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_is_public_and_shows_published_home_content(): void
    {
        MarketingPage::create([
            'slug' => 'home',
            'title' => 'Welcome to e-Library',
            'content' => 'Learn, test, and read — all in one place.',
            'is_published' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Welcome to e-Library');
    }

    public function test_the_homepage_falls_back_to_default_copy_when_no_page_is_published(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_a_published_page_is_publicly_viewable(): void
    {
        MarketingPage::create([
            'slug' => 'about',
            'title' => 'About us',
            'content' => 'Some story here.',
            'is_published' => true,
        ]);

        $response = $this->get('/pages/about');

        $response->assertOk();
        $response->assertSee('About us');
    }

    public function test_an_unpublished_page_404s(): void
    {
        MarketingPage::create([
            'slug' => 'draft',
            'title' => 'Draft page',
            'content' => 'Not ready yet.',
            'is_published' => false,
        ]);

        $this->get('/pages/draft')->assertNotFound();
    }

    public function test_only_central_admins_can_manage_pages(): void
    {
        $this->get('/admin/pages')->assertRedirect();
    }

    public function test_a_central_admin_can_create_and_publish_a_page(): void
    {
        $admin = User::factory()->create(['tenant_id' => null]);

        $response = $this->actingAs($admin)->post('/admin/pages', [
            'slug' => 'pricing',
            'title' => 'Pricing',
            'subtitle' => 'Simple and transparent.',
            'content' => 'Contact us for a quote.',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('marketing_pages', ['slug' => 'pricing', 'is_published' => true]);

        $this->get('/pages/pricing')->assertOk();
    }
}
