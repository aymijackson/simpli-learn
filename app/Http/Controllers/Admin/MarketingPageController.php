<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\ActivityLog;

class MarketingPageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => MarketingPage::orderBy('slug')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        MarketingPage::create($this->validated($request));

        ActivityLog::record('website.page_created', 'Created a website page');

        return redirect()->route('admin.pages.index')->with('status', 'Page created.');
    }

    public function edit(MarketingPage $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, MarketingPage $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        ActivityLog::record('website.page_updated', "Updated the website page {$page->title}", $page);

        return redirect()->route('admin.pages.index')->with('status', 'Page updated.');
    }

    public function destroy(MarketingPage $page): RedirectResponse
    {
        $page->delete();

        ActivityLog::record('website.page_deleted', "Deleted the website page {$page->title}");

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    private function validated(Request $request, ?MarketingPage $page = null): array
    {
        $validated = $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:50', Rule::unique('marketing_pages', 'slug')->ignore($page)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }
}
