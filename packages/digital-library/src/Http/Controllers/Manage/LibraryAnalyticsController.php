<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryAnalyticsController extends Controller
{
    public function index(): View
    {
        $checkouts = LibraryCheckout::query()->get();
        $activeCount = $checkouts->whereNull('returned_at')->count();
        $overdueCount = $checkouts->filter(fn (LibraryCheckout $checkout) => $checkout->isOverdue())->count();
        $returned = $checkouts->whereNotNull('returned_at');
        $avgDurationDays = $returned->isEmpty() ? null : round(
            $returned->avg(fn (LibraryCheckout $checkout) => $checkout->checked_out_at->diffInDays($checkout->returned_at)),
            1
        );

        $totalRevenue = LibraryResourcePurchase::query()->where('status', 'paid')->sum('amount');

        $mostBorrowed = LibraryResource::withCount('checkouts')
            ->orderByDesc('checkouts_count')
            ->take(10)
            ->get();

        return view('library::manage.analytics.index', [
            'totalCheckouts' => $checkouts->count(),
            'activeCount' => $activeCount,
            'overdueCount' => $overdueCount,
            'avgDurationDays' => $avgDurationDays,
            'totalRevenue' => $totalRevenue,
            'mostBorrowed' => $mostBorrowed,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $resources = LibraryResource::withCount('checkouts')->orderByDesc('checkouts_count')->get();

        return response()->streamDownload(function () use ($resources) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Resource', 'Category', 'Total checkouts', 'Currently active', 'Total copies']);

            foreach ($resources as $resource) {
                fputcsv($handle, [
                    $resource->title,
                    $resource->category,
                    $resource->checkouts_count,
                    $resource->activeCheckoutsCount(),
                    $resource->total_copies ?? 'unlimited',
                ]);
            }

            fclose($handle);
        }, Str::slug('library-analytics-'.now()->format('Y-m-d')).'.csv', ['Content-Type' => 'text/csv']);
    }
}
