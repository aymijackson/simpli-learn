<?php

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use Elibrary\Cbt\Models\Certificate;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    /**
     * Public, unauthenticated, no tenant context — the certificate's
     * verification_token (an unguessable 48-char random string, never a
     * sequential id) is the sole lookup key, so this scope bypass only
     * ever resolves a single known certificate, never a listing.
     *
     * The eager-loaded relations each carry their own independent
     * TenantScope (User/Exam/ExamAttempt all use BelongsToTenant) — since
     * this route has no {tenant} segment at all, that scope would otherwise
     * filter every one of them to "tenant_id IS NULL" and silently return
     * nothing, so each relation's own query needs the same explicit bypass,
     * not just the top-level Certificate query.
     */
    public function show(string $token): View
    {
        $certificate = Certificate::withoutGlobalScope(TenantScope::class)
            ->with([
                'user' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                'exam' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                'attempt' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
            ])
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('certificates.verify', ['certificate' => $certificate]);
    }
}
