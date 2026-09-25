<?php

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use Elibrary\Lms\Models\CourseCertificate;
use Illuminate\View\View;

class CourseCertificateVerificationController extends Controller
{
    /**
     * Public, unauthenticated, no tenant context — mirrors
     * CertificateVerificationController's shape exactly, including bypassing
     * TenantScope on every eager-loaded relation (not just the top-level
     * query) — the gotcha hit and fixed earlier this session.
     */
    public function show(string $token): View
    {
        $certificate = CourseCertificate::withoutGlobalScope(TenantScope::class)
            ->with([
                'user' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                'course' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
            ])
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('course-certificates.verify', ['certificate' => $certificate]);
    }
}
