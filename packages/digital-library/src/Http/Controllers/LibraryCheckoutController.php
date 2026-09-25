<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Checkouts\LibraryCheckoutService;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryCheckoutController extends Controller
{
    public function myCheckouts(Request $request): View
    {
        $user = $request->user();

        return view('library::checkouts.index', [
            'checkouts' => LibraryCheckout::with('resource')->where('user_id', $user->id)->latest('checked_out_at')->get(),
            'holds' => LibraryHold::with('resource')->where('user_id', $user->id)->whereNull('fulfilled_at')->latest('requested_at')->get(),
        ]);
    }

    public function borrow(Request $request, string $tenant, LibraryResource $resource, LibraryCheckoutService $service): RedirectResponse
    {
        abort_unless($resource->is_published && $resource->requires_checkout, 404);

        $result = $service->borrow($resource, $request->user());

        $status = $result instanceof LibraryCheckout
            ? "You've borrowed \"{$resource->title}\" — due back ".$result->due_at->format('M j, Y').'.'
            : "No copies available right now — you're #{$result->position()} on the waitlist for \"{$resource->title}\".";

        return redirect()->route('library.resources.show', $resource)->with('status', $status);
    }

    public function return(Request $request, string $tenant, LibraryCheckout $checkout, LibraryCheckoutService $service): RedirectResponse
    {
        abort_unless($checkout->user_id === $request->user()->id, 403);

        $service->return($checkout);

        return redirect()->route('library.checkouts.index')->with('status', 'Returned. Thanks!');
    }

    public function claimHold(Request $request, string $tenant, LibraryHold $hold, LibraryCheckoutService $service): RedirectResponse
    {
        abort_unless($hold->user_id === $request->user()->id, 403);

        $service->claim($hold);

        return redirect()->route('library.resources.show', $hold->resource)->with('status', 'Your hold has been checked out.');
    }
}
