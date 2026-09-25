<?php

namespace Elibrary\Cbt\Certificates;

use Elibrary\Cbt\Enums\CertificatePolicy;
use Elibrary\Cbt\Enums\CertificateTier;
use Elibrary\Cbt\Models\Certificate;
use Elibrary\Cbt\Models\ExamAttempt;

class CertificateService
{
    /**
     * Issues a certificate immediately for policies that don't require payment
     * first. Free -> a full "verified" certificate. Freemium -> an "unverified"
     * one now, upgradeable later via CertificatePaymentController. Paid ->
     * nothing here; that flow creates the certificate only once payment confirms.
     */
    public function issueIfFree(ExamAttempt $attempt): void
    {
        if (! $attempt->passed() || $attempt->certificate) {
            return;
        }

        $policy = $attempt->exam->certificatePolicy();

        match ($policy) {
            CertificatePolicy::Free => Certificate::issueFor($attempt, CertificateTier::Verified),
            CertificatePolicy::Freemium => Certificate::issueFor($attempt, CertificateTier::Unverified),
            default => null,
        };
    }
}
