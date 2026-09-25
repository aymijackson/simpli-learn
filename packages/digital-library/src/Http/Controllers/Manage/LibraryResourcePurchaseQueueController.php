<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryPurchaseStatus;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryResourcePurchaseQueueController extends Controller
{
    public function index(): View
    {
        return view('library::manage.resource-purchases.index', [
            'purchases' => LibraryResourcePurchase::with(['user', 'resource'])->latest()->paginate(20),
        ]);
    }

    public function confirm(Request $request, string $tenant, LibraryResourcePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->isPending(), 404);

        $purchase->markPaid($request->user()->id);

        return back()->with('status', 'Payment confirmed.');
    }

    public function reject(string $tenant, LibraryResourcePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->isPending(), 404);

        $purchase->update(['status' => LibraryPurchaseStatus::Cancelled->value]);

        return back()->with('status', 'Payment marked as cancelled.');
    }
}
