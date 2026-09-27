<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Owners create and revoke the workspace's API keys; the page doubles as the API docs. */
class ApiTokenController extends Controller
{
    public function index(): View
    {
        return view('settings.api', [
            'tokens' => ApiToken::with('createdBy')->latest()->get(),
            'baseUrl' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'access' => ['required', 'in:read,write'],
        ]);

        [$token, $plain] = ApiToken::issue(app(Tenancy::class)->id(), $request->user(), $validated['name'], $validated['access'] === 'write');
        ActivityLog::record('settings.api_key_created', "Created the API key \"{$token->name}\" (".($token->can_write ? 'read and write' : 'read only').')', $token);

        // Shown once; only its hash is kept.
        return redirect()->route('tenant.settings.api')->with('new_api_key', $plain);
    }

    public function destroy(string $tenant, int $token): RedirectResponse
    {
        $token = ApiToken::whereNull('revoked_at')->findOrFail($token);
        $token->forceFill(['revoked_at' => now()])->save();
        ActivityLog::record('settings.api_key_revoked', "Revoked the API key \"{$token->name}\"", $token);

        return redirect()->route('tenant.settings.api')->with('status', "API key \"{$token->name}\" revoked. Anything using it will stop working.");
    }
}
