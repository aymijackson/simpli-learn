<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

class Tenancy
{
    protected ?Tenant $tenant = null;

    /**
     * Whether content other workspaces share with this one (public courses and
     * what's inside them) is visible. On by default once a tenant is set; the
     * back office turns it off so owners only ever see and edit their own.
     */
    protected bool $sharedContent = false;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->sharedContent = true;
    }

    public function forget(): void
    {
        $this->tenant = null;
        $this->sharedContent = false;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function shareContent(bool $share = true): void
    {
        $this->sharedContent = $share;
    }

    public function sharesContent(): bool
    {
        return $this->tenant !== null && $this->sharedContent;
    }
}
