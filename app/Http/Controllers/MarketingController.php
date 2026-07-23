<?php

namespace App\Http\Controllers;

use App\Models\MarketingPage;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home', [
            'page' => MarketingPage::where('slug', 'home')->where('is_published', true)->first(),
        ]);
    }

    public function show(MarketingPage $page): View
    {
        abort_unless($page->is_published, 404);

        return view('marketing.page', [
            'page' => $page,
        ]);
    }
}
