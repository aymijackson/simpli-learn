<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Support\PersonalData;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\ActivityLog;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * "Profile & security" for every signed-in user: tenant members and owners
 * at /t/{tenant}/profile, central admins at /profile.
 */
class ProfileController extends Controller
{
    public function edit(Request $request, Tenancy $tenancy): View
    {
        return view($tenancy->current() ? 'profile.tenant' : 'profile.central', [
            'user' => $request->user(),
            'routes' => $this->routes($tenancy),
        ]);
    }

    public function update(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('details', [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                // Emails are unique within a workspace (or among central admins).
                Rule::unique('users')
                    ->where(fn ($query) => $user->tenant_id ? $query->where('tenant_id', $user->tenant_id) : $query->whereNull('tenant_id'))
                    ->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'locale' => ['nullable', 'in:'.implode(',', array_keys(config('languages.available')))],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        if ($user->tenant_id && $request->exists('job_title')) {
            $validated['job_title_id'] = $this->jobTitleId($user, (string) $request->input('job_title'));
        }
        unset($validated['job_title']);

        $user->fill($validated)->save();

        ActivityLog::record('profile.updated', 'Updated their name or email');

        return redirect($this->routes($tenancy)['edit'])->with('status', __('Your details have been updated.'));
    }

    public function updatePassword(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'password.different' => 'Choose a new password that is different from your current one.',
        ]);

        $request->user()->forceFill(['password' => $validated['password']])->save();

        ActivityLog::record('profile.password_changed', 'Changed their password');

        return redirect($this->routes($tenancy)['edit'])->with('status', __('Your password has been changed.'));
    }

    /** Right of access for the signed-in person. */
    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();
        ActivityLog::record('data.exported', 'Downloaded their own personal data');

        return response()->streamDownload(
            fn () => print(json_encode(PersonalData::export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'my-data-'.Str::slug($user->name ?: 'user').'-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    /** @return array<string, string> */
    /**
     * Owners may add a new job title; everyone else picks an existing one,
     * so the list doesn't fill with near-duplicates.
     */
    private function jobTitleId(User $user, string $name): ?int
    {
        if (JobTitle::tidy($name) === '') {
            return null;
        }

        $title = $user->isOwner()
            ? JobTitle::findOrCreateByName($name, $user->tenant_id)
            : JobTitle::findByName($name, $user->tenant_id);

        if (! $title) {
            throw ValidationException::withMessages(['job_title' => __('Pick a job title from the list. If yours is missing, ask your administrator to add it.')])->errorBag('details');
        }

        return $title->id;
    }

    private function routes(Tenancy $tenancy): array
    {
        $prefix = $tenancy->current() ? 'tenant.' : '';

        return [
            'edit' => route($prefix.'profile.edit'),
            'update' => route($prefix.'profile.update'),
            'password' => route($prefix.'profile.password'),
            'export' => route($prefix.'profile.export'),
            'twoFactorEnable' => route($prefix.'profile.two-factor.enable'),
            'twoFactorConfirm' => route($prefix.'profile.two-factor.confirm'),
            'twoFactorCancel' => route($prefix.'profile.two-factor.cancel'),
            'twoFactorDisable' => route($prefix.'profile.two-factor.disable'),
            'twoFactorRecoveryCodes' => route($prefix.'profile.two-factor.recovery-codes'),
        ];
    }
}
