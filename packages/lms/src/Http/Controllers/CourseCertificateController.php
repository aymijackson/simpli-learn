<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Elibrary\Lms\Models\Course;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CourseCertificateController extends Controller
{
    public function download(Request $request, string $tenant, Course $course): Response
    {
        $certificate = $course->certificateFor($request->user());
        abort_unless($certificate, 404);

        $certificate->loadMissing(['user', 'course']);

        $verificationUrl = route('course-certificates.verify', $certificate->verification_token);
        $qrDataUri = $this->qrDataUri($verificationUrl);

        $pdf = Pdf::loadView('lms::course-certificates.pdf', [
            'certificate' => $certificate,
            'course' => $course,
            'qrDataUri' => $qrDataUri,
            'verificationUrl' => $verificationUrl,
        ]);

        return $pdf->download(Str::slug($course->title).'-certificate.pdf');
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
