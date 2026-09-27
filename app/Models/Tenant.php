<?php

namespace App\Models;

use App\Enums\Module;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'status',
        'logo_path',
        'brand_color',
        'sso_providers',
        'sso_domains',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'sso_providers' => 'array',
            'sso_domains' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function revenueSplit(): HasOne
    {
        return $this->hasOne(TenantRevenueSplit::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    public function hasModule(Module|string $module): bool
    {
        $module = $module instanceof Module ? $module : Module::from($module);

        return $this->tenantModules->contains(
            fn (TenantModule $tenantModule) => $tenantModule->module === $module && $tenantModule->is_enabled
        );
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === TenantStatus::Pending;
    }
}
