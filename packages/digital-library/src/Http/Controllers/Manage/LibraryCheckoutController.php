<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Library\Checkouts\LibraryCheckoutService;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LibraryCheckoutController extends Controller
{
    public function index(string $tenant, LibraryResource $resource): View
    {
        return view('library::manage.resources.checkouts', [
            'resource' => $resource,
            'activeCheckouts' => $resource->checkouts()->with('user')->whereNull('returned_at')->orderBy('due_at')->get(),
            'holds' => $resource->holds()->with('user')->whereNull('fulfilled_at')->orderBy('requested_at')->get(),
        ]);
    }

    public function forceReturn(string $tenant, LibraryResource $resource, LibraryCheckout $checkout, LibraryCheckoutService $service): RedirectResponse
    {
        abort_unless($checkout->resource_id === $resource->id, 404);

        $service->return($checkout);

        return back()->with('status', 'Marked as returned.');
    }
}
