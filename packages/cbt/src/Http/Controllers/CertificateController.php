<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Elibrary\Cbt\Enums\CertificateTier;
use Elibrary\Cbt\Models\ExamAttempt;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function download(Request $request, string $tenant, ExamAttempt $attempt): Response
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        $certificate = $attempt->certificate;
        abort_unless($certificate, 404);

        $certificate->loadMissing(['user', 'exam', 'attempt']);
        $isVerified = $certificate->tier === CertificateTier::Verified;

        $verificationUrl = route('certificates.verify', $certificate->verification_token);
        $qrDataUri = $isVerified ? $this->qrDataUri($verificationUrl) : null;

        $settings = $certificate->exam->certificateSettings();

        $pdf = Pdf::loadView('cbt::certificates.pdf', [
            'certificate' => $certificate,
            'exam' => $certificate->exam,
            'isVerified' => $isVerified,
            'qrDataUri' => $qrDataUri,
            'verificationUrl' => $verificationUrl,
            'issuerName' => $settings->issuer_name ?? $certificate->exam->tenant->name,
            'signatoryName' => $settings->signatory_name ?? '',
            'signatoryTitle' => $settings->signatory_title ?? '',
        ]);

        return $pdf->download(Str::slug($certificate->exam->title).'-certificate.pdf');
    }

    private function qrDataUri(string $url): string
    {
        return QrCodeBuilder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(200)
            ->margin(10)
            ->build()
            ->getDataUri();
    }
}
