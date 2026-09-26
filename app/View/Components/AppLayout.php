<?php

namespace App\View\Components;

use App\Enums\Module;
use App\Http\Controllers\ManageDashboardController;
use App\Models\Tenant;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class AppLayout extends Component
{
    /**
     * Routes that belong to the owner's back office. They render inside the
     * sidebar layout; everything else gets the learner-facing layout.
     */
    public const MANAGE_ROUTES = ['*.manage.*', 'tenant.team.*'];

    public Tenant $tenant;

    public Collection $enabledModules;

    /** Bank transfers awaiting confirmation, keyed by module value (sidebar badges). */
    public array $reviews = [];

    /**
     * @param  bool  $flush  Let the page control its own width and padding
     *                       (for full-bleed heroes) instead of the default container.
     */
    public function __construct(public ?string $title = null, public bool $flush = false)
    {
        $this->tenant = app(Tenancy::class)->current();
        $this->enabledModules = $this->tenant->tenantModules()->where('is_enabled', true)->get()
            ->sortBy(fn ($tenantModule) => array_search($tenantModule->module, Module::cases(), true))
            ->values();

        if ($this->isManageRoute()) {
            $this->reviews = ManageDashboardController::pendingPaymentReviews(
                $this->enabledModules->map(fn ($tenantModule) => $tenantModule->module)
            );

            if ($this->hasModule(Module::Cbt)) {
                $toMark = ExamAttempt::where('needs_marking', true)->whereNotNull('submitted_at')->count();
                if ($toMark > 0) {
                    $this->reviews['marking'] = $toMark;
                }
            }
        }
    }

    public function isManageRoute(): bool
    {
        return request()->routeIs(...self::MANAGE_ROUTES);
    }

    public function hasModule(Module $module): bool
    {
        return $this->enabledModules->contains(fn ($tenantModule) => $tenantModule->module === $module);
    }

    public function render()
    {
        return $this->isManageRoute()
            ? view('components.manage-layout')
            : view('components.layout');
    }
}
