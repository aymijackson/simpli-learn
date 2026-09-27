<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $learner;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Basics', 'slug' => 'basics', 'is_published' => true]);
        $this->lesson = $this->course->lessons()->create([
            'tenant_id' => $this->tenant->id, 'title' => 'Watch this', 'content' => '', 'position' => 0,
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'require_video_watch' => true,
        ]);
    }

    private function url(string $suffix = ''): string
    {
        return "/t/acme/lms/courses/basics/lessons/{$this->lesson->id}{$suffix}";
    }

    public function test_links_from_the_three_hosts_become_players(): void
    {
        $cases = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s' => ['youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'https://youtu.be/dQw4w9WgXcQ' => ['youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'https://www.youtube.com/shorts/dQw4w9WgXcQ' => ['youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'https://vimeo.com/76979871' => ['vimeo', 'https://player.vimeo.com/video/76979871?dnt=1'],
            'https://vimeo.com/76979871/8272103f6e' => ['vimeo', 'https://player.vimeo.com/video/76979871?dnt=1&h=8272103f6e'],
            'https://player.vimeo.com/video/76979871?h=8272103f6e' => ['vimeo', 'https://player.vimeo.com/video/76979871?dnt=1&h=8272103f6e'],
            'https://iframe.mediadelivery.net/play/12345/0b1f7d2e-3c4a-4b5c-9d6e-7f8a9b0c1d2e' => ['bunny', 'https://iframe.mediadelivery.net/embed/12345/0b1f7d2e-3c4a-4b5c-9d6e-7f8a9b0c1d2e'],
        ];

        foreach ($cases as $url => [$provider, $embedStart]) {
            $video = VideoEmbed::parse($url);
            $this->assertSame($provider, $video['provider'] ?? null, $url);
            $this->assertStringStartsWith($embedStart, $video['embed_url'], $url);
        }

        foreach (['https://example.com/video.mp4', 'https://www.youtube.com/watch?v=bad', 'javascript:alert(1)', 'https://vimeo.com/channels/staffpicks', ''] as $bad) {
            $this->assertNull(VideoEmbed::parse($bad), $bad);
        }
    }

    public function test_owners_add_a_video_link_and_unknown_links_are_refused(): void
    {
        $owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)->put("/t/acme/lms/manage/courses/basics/lessons/{$this->lesson->id}", [
            'title' => 'Watch this', 'position' => 0, 'video_url' => 'https://example.com/clip.mp4',
        ])->assertSessionHasErrors('video_url');

        $this->actingAs($owner)->put("/t/acme/lms/manage/courses/basics/lessons/{$this->lesson->id}", [
            'title' => 'Watch this', 'position' => 0, 'video_url' => 'https://vimeo.com/76979871',
        ])->assertSessionHasNoErrors();
        $this->assertSame('https://vimeo.com/76979871', $this->lesson->fresh()->video_url);
        $this->assertFalse($this->lesson->fresh()->require_video_watch); // unticked

        // Without a video, notes are required.
        $this->actingAs($owner)->put("/t/acme/lms/manage/courses/basics/lessons/{$this->lesson->id}", [
            'title' => 'Watch this', 'position' => 0,
        ])->assertSessionHasErrors('content');
    }

    public function test_the_lesson_must_be_watched_before_it_completes(): void
    {
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');

        $this->actingAs($this->learner)->get($this->url())
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Watch the video to complete this lesson')
            ->assertDontSee('Mark as complete');

        $this->actingAs($this->learner)->post($this->url('/complete'))->assertSessionHas('error');
        $this->assertFalse($this->lesson->isCompletedBy($this->learner));

        $this->actingAs($this->learner)->postJson($this->url('/video-progress'), ['percent' => 50])
            ->assertOk()->assertJson(['percent' => 50, 'completed' => false]);
        // Progress never goes backwards.
        $this->actingAs($this->learner)->postJson($this->url('/video-progress'), ['percent' => 20])->assertJson(['percent' => 50]);

        $this->actingAs($this->learner)->postJson($this->url('/video-progress'), ['percent' => 92])
            ->assertOk()->assertJson(['percent' => 92, 'completed' => true]);
        $this->assertTrue($this->lesson->isCompletedBy($this->learner));
        $this->assertSame(100, $this->course->progressPercentFor($this->learner));
    }

    public function test_optional_watching_keeps_the_complete_button(): void
    {
        $this->lesson->update(['require_video_watch' => false]);
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');

        $this->actingAs($this->learner)->get($this->url())->assertSee('Mark as complete');
        $this->actingAs($this->learner)->post($this->url('/complete'))->assertSessionHas('status');
        $this->assertTrue($this->lesson->isCompletedBy($this->learner));
    }

    public function test_only_enrolled_learners_report_progress(): void
    {
        $this->actingAs($this->learner)->postJson($this->url('/video-progress'), ['percent' => 95])->assertForbidden();
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');
        $this->actingAs($this->learner)->postJson($this->url('/video-progress'), ['percent' => 150])->assertUnprocessable();
    }

    public function test_the_security_policy_allows_only_the_known_players(): void
    {
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');
        $policy = $this->actingAs($this->learner)->get($this->url())->headers->get('Content-Security-Policy-Report-Only')
            ?? $this->actingAs($this->learner)->get($this->url())->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('frame-src \'self\' blob: https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://iframe.mediadelivery.net', $policy);
    }
}
