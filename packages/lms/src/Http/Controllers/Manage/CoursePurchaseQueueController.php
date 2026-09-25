<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Enums\CoursePurchaseStatus;
use Elibrary\Lms\Models\CoursePurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoursePurchaseQueueController extends Controller
{
    public function index(): View
    {
        return view('lms::manage.course-purchases.index', [
            'purchases' => CoursePurchase::with(['user', 'course'])->latest()->paginate(20),
        ]);
    }

    public function confirm(Request $request, string $tenant, CoursePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->isPending(), 404);

        $purchase->markPaidAndActivate($request->user()->id);

        return back()->with('status', 'Payment confirmed.');
    }

    public function reject(string $tenant, CoursePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->isPending(), 404);

        $purchase->update(['status' => CoursePurchaseStatus::Cancelled->value]);

        return back()->with('status', 'Payment marked as cancelled.');
    }
}
