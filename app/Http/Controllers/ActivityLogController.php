<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only views of the activity log: owners see their own workspace at
 * /t/{tenant}/manage/activity, central admins see everything at /admin/activity.
 */
class ActivityLogController extends Controller
{
    /** Filter categories: action prefix => [label, icon]. */
    public const CATEGORIES = [
        'auth' => ['Sign-ins', 'user-circle'],
        'security' => ['Security', 'shield-check'],
        'profile' => ['Profile changes', 'pencil'],
        'team' => ['Team', 'users'],
        'data' => ['Data requests', 'document'],
        'payments' => ['Payments', 'receipt'],
        'settings' => ['Settings', 'adjustments'],
        'workspace' => ['Workspaces', 'building'],
        'impersonation' => ['Admin access', 'eye'],
        'website' => ['Website', 'globe'],
    ];

    public function tenantIndex(Request $request, Tenancy $tenancy): View
    {
        $query = ActivityLog::where('tenant_id', $tenancy->current()->id);

        return view('activity.tenant', $this->listing($request, $query));
    }

    public function adminIndex(Request $request): View
    {
        return view('activity.admin', $this->listing($request, ActivityLog::with('tenant')));
    }

    private function listing(Request $request, Builder $query): array
    {
        $category = $request->string('category')->value();
        if (array_key_exists($category, self::CATEGORIES)) {
            $query->where('action', 'like', $category.'.%');
        }

        $search = $request->string('q')->trim()->limit(100, '')->value();
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($inner) => $inner->where('actor_name', 'like', $like)->orWhere('description', 'like', $like));
        }

        return [
            'entries' => $query->latest('id')->paginate(50)->withQueryString(),
            'categories' => self::CATEGORIES,
            'activeCategory' => array_key_exists($category, self::CATEGORIES) ? $category : '',
            'search' => $search,
        ];
    }
}
