<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Enums\UserRole;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Enrollment;
use App\Models\User;
use App\Notifications\AddedToWorkspace;
use App\Support\Invitations;
use App\Support\PersonalData;
use App\Support\SafeNotifier;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Models\ActivityLog;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('team.index', [
            'members' => app(Tenancy::class)->current()->users()->active()->orderBy('name')->get(),
            'deactivated' => app(Tenancy::class)->current()->users()->whereNotNull('deactivated_at')->orderBy('name')->get(),
        ]);
    }

    /** A member's profile: details, learning progress, results, loans and recent activity. */
    public function show(string $tenant, User $member): View
    {
        $workspace = app(Tenancy::class)->current();
        $enabled = $workspace->tenantModules()->where('is_enabled', true)->pluck('module')
            ->map(fn ($module) => $module instanceof Module ? $module : Module::from($module));

        $courses = collect();
        $attempts = collect();
        $loans = collect();

        if ($enabled->contains(Module::Lms)) {
            $courses = Course::whereIn('id', Enrollment::where('user_id', $member->id)->pluck('course_id'))
                ->withCount('lessons')
                ->get()
                ->map(function (Course $course) use ($member) {
                    $course->progress = $course->progressPercentFor($member);
                    $course->enrolled_at = Enrollment::where('user_id', $member->id)->where('course_id', $course->id)->value('enrolled_at');

                    return $course;
                })
                ->sortByDesc('progress')
                ->values();
        }

        if ($enabled->contains(Module::Cbt)) {
            $attempts = ExamAttempt::where('user_id', $member->id)
                ->whereNotNull('submitted_at')
                ->with('exam')
                ->latest('submitted_at')
                ->take(20)
                ->get()
                ->filter(fn (ExamAttempt $attempt) => $attempt->exam !== null);
        }

        if ($enabled->contains(Module::Library)) {
            $loans = LibraryCheckout::where('user_id', $member->id)
                ->with('resource')
                ->latest('checked_out_at')
                ->take(20)
                ->get()
                ->filter(fn (LibraryCheckout $loan) => $loan->resource !== null);
        }

        return view('team.show', [
            'member' => $member,
            'enabled' => $enabled,
            'courses' => $courses,
            'attempts' => $attempts,
            'loans' => $loans,
            'lastSignIn' => ActivityLog::where('user_id', $member->id)->where('action', 'auth.login')->latest('id')->value('created_at'),
            'activity' => ActivityLog::where('tenant_id', $workspace->id)
                ->where(fn ($query) => $query->where('user_id', $member->id)
                    ->orWhere(fn ($about) => $about->where('subject_type', $member->getMorphClass())->where('subject_id', $member->id)))
                ->latest('id')
                ->take(10)
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('team.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app(Tenancy::class)->current();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            // Optional: leave blank to email an invitation to set their own password.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $invite = empty($validated['password']);

        $member = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($invite ? Str::random(64) : $validated['password']),
        ]);

        ActivityLog::record('team.member_added', "Added {$member->name} ({$member->email}) as {$member->role->label()}", $member);

        $emailed = $invite
            ? Invitations::send($member, $tenant, $request->user()->name)
            : SafeNotifier::send($member, new AddedToWorkspace($tenant, $request->user()->name));

        return redirect()->route('tenant.team.index')->with('status', match (true) {
            ! $emailed => 'Team member added. (The email could not be sent — check the mail settings, then use "Resend invitation".)',
            $invite => "Team member added. We've emailed {$member->email} an invitation to set their password.",
            default => "Team member added. We've emailed {$member->email} their login link.",
        });
    }

    public function update(Request $request, string $tenant, User $member): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        if ($member->isOwner() && $validated['role'] !== UserRole::Owner->value && $this->isLastOwner($member)) {
            return back()->withErrors(['role' => 'You cannot demote the only owner.']);
        }

        $member->update(['role' => $validated['role']]);

        ActivityLog::record('team.role_changed', "Changed {$member->name}'s role to {$member->role->label()}", $member);

        return redirect()->route('tenant.team.index')->with('status', 'Role updated.');
    }

    /**
     * "Remove" deactivates rather than deletes: deleting a user cascades to
     * their payments, exam attempts and course history, which must be kept.
     */
    public function destroy(string $tenant, User $member): RedirectResponse
    {
        if ($member->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot deactivate yourself.']);
        }

        if ($member->isOwner() && $this->isLastOwner($member)) {
            return back()->withErrors(['member' => 'You cannot deactivate the only owner.']);
        }

        $member->forceFill(['deactivated_at' => now()])->save();
        ActivityLog::record('team.member_deactivated', "Deactivated {$member->name} ({$member->email})", $member);

        return redirect()->route('tenant.team.index')->with('status', "{$member->name} has been deactivated and can no longer sign in. Their records are kept, and you can reactivate them at any time.");
    }

    public function reactivate(string $tenant, User $member): RedirectResponse
    {
        $member->forceFill(['deactivated_at' => null])->save();
        ActivityLog::record('team.member_reactivated', "Reactivated {$member->name} ({$member->email})", $member);

        return redirect()->route('tenant.team.index')->with('status', "{$member->name} has been reactivated and can sign in again.");
    }

    /** Right of access: everything held about a member, as a JSON download. */
    public function exportData(string $tenant, User $member): StreamedResponse
    {
        ActivityLog::record('data.exported', "Downloaded the personal data of {$member->name}", $member);

        return $this->jsonDownload(PersonalData::export($member), $member);
    }

    /** Right to erasure: anonymise the member (see PersonalData::erase). */
    public function eraseData(string $tenant, User $member): RedirectResponse
    {
        if ($member->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot erase your own account from here.']);
        }

        if ($member->isOwner() && $this->isLastOwner($member)) {
            return back()->withErrors(['member' => 'You cannot erase the only owner.']);
        }

        PersonalData::erase($member);
        ActivityLog::record('data.erased', "Erased the personal data of a team member (account #{$member->id})", $member);

        return redirect()->route('tenant.team.index')->with('status', 'Their personal data has been erased. Payment and exam records are kept anonymously.');
    }

    /** Send a fresh "set your password" link (e.g. the first one expired). */
    public function resendInvitation(Request $request, string $tenant, User $member): RedirectResponse
    {
        $sent = Invitations::send($member, app(Tenancy::class)->current(), $request->user()->name);
        ActivityLog::record('team.invitation_sent', "Sent {$member->name} a new invitation", $member);

        return back()->with('status', $sent
            ? "A new invitation has been emailed to {$member->email}."
            : 'The invitation could not be sent — check the mail settings.');
    }

    /** For a member who lost their phone and recovery codes. */
    public function resetTwoFactor(string $tenant, User $member): RedirectResponse
    {
        $member->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        ActivityLog::record('security.two_factor_reset', "Reset two-step login for {$member->name}", $member);

        return redirect()->route('tenant.team.index')->with('status', "Two-step login was reset for {$member->name}. They can sign in with their password and set it up again.");
    }

    private function jsonDownload(array $data, User $member): StreamedResponse
    {
        $filename = 'personal-data-'.\Illuminate\Support\Str::slug($member->name ?: 'user').'-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    private function isLastOwner(User $member): bool
    {
        return $member->tenant->users()->active()->where('role', UserRole::Owner)->count() <= 1;
    }
}
