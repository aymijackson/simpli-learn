<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\BrandColor;
use App\Support\SingleSignOn;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The owner's workspace name, logo and brand colour. */
class WorkspaceSettingsController extends Controller
{
    public function edit(Tenancy $tenancy): View
    {
        return view('settings.edit', [
            'workspace' => $tenancy->current(),
            'presets' => BrandColor::PRESETS,
            'ssoAvailable' => SingleSignOn::available(),
        ]);
    }

    public function update(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $workspace = $tenancy->current();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/', function ($attribute, $value, $fail) {
                if (BrandColor::isValidHex($value) && BrandColor::contrastWithWhite($value) < BrandColor::MIN_CONTRAST) {
                    $fail('That colour is too light for white button text to be readable — choose a darker shade.');
                }
            }],
            // No SVG: an uploaded SVG could carry script and is served from this site.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
            'sso_providers' => ['nullable', 'array'],
            'sso_providers.*' => [Rule::in(array_keys(SingleSignOn::available()))],
            'sso_domains' => ['nullable', 'string', 'max:500'],
        ], [
            'brand_color.regex' => 'Use a colour like #1d4ed8.',
            'logo.mimes' => 'Upload a PNG, JPG or WebP image.',
        ]);

        $changes = ['name' => $validated['name']];
        // Only touch the colour when the form sent the field (empty = back to the default).
        if ($request->exists('brand_color')) {
            $changes['brand_color'] = filled($validated['brand_color'] ?? null) ? strtolower($validated['brand_color']) : null;
        }

        // Sign-in with Google/Microsoft, and which email domains may join by themselves.
        $domains = collect(preg_split('/[\s,;]+/', strtolower((string) ($validated['sso_domains'] ?? ''))))
            ->map(fn ($domain) => ltrim(trim($domain), '@'))
            ->filter()
            ->unique()
            ->values();
        $invalid = $domains->reject(fn ($domain) => preg_match('/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/', $domain));
        if ($invalid->isNotEmpty()) {
            return back()->withErrors(['sso_domains' => 'Not a valid domain: '.$invalid->implode(', ')])->withInput();
        }
        $changes['sso_providers'] = array_values($validated['sso_providers'] ?? []) ?: null;
        $changes['sso_domains'] = $domains->all() ?: null;

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($workspace->logo_path) {
                Storage::disk('public')->delete($workspace->logo_path);
            }
            $changes['logo_path'] = $request->hasFile('logo')
                ? $request->file('logo')->storeAs('tenant-logos/'.$workspace->id, Str::random(40).'.'.$request->file('logo')->extension(), 'public')
                : null;
        }

        $workspace->update($changes);
        ActivityLog::record('settings.branding', 'Updated the workspace name and branding', $workspace);

        return redirect()->route('tenant.settings.edit')->with('status', 'Workspace settings saved.');
    }
}
