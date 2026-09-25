<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Models\MarketingPage;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home', [
            'page' => MarketingPage::where('slug', 'home')->where('is_published', true)->first(),
            'workspaceCount' => Tenant::where('status', TenantStatus::Active)->count(),
        ]);
    }

    public function show(MarketingPage $page): View
    {
        abort_unless($page->is_published, 404);

        return view('marketing.page', [
            'page' => $page,
        ]);
    }

    /**
     * Send a learner to their organization's login page from the public
     * site, matching the workspace address or its exact name.
     */
    public function findWorkspace(Request $request): RedirectResponse
    {
        $input = trim((string) $request->string('workspace'));

        // Accept a pasted URL like https://example.com/t/acme/login as well as a bare slug.
        if (preg_match('#/t/([^/?\#]+)#', $input, $matches)) {
            $input = $matches[1];
        }

        if ($input !== '') {
            $tenant = Tenant::where('status', TenantStatus::Active)
                ->where(fn ($query) => $query->where('slug', Str::slug($input))->orWhere('name', $input))
                ->first();

            if ($tenant) {
                return redirect()->route('tenant.login', $tenant->slug);
            }
        }

        return redirect()->to(route('home').'#find-workspace')
            ->withInput()
            ->with('workspace_error', $input === ''
                ? 'Enter your organization’s workspace address.'
                : "We couldn't find an active workspace called “{$input}”. Check the address your organization gave you.");
    }
}
