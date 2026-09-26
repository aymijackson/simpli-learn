<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\PaymentStatus;
use Elibrary\Cbt\Models\CertificatePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\ActivityLog;

class CertificatePaymentQueueController extends Controller
{
    public function index(): View
    {
        return view('cbt::manage.certificate-payments.index', [
            'payments' => CertificatePayment::with(['user', 'attempt.exam'])->latest()->paginate(20),
        ]);
    }

    public function confirm(Request $request, string $tenant, CertificatePayment $payment): RedirectResponse
    {
        abort_unless($payment->isPending(), 404);

        $payment->markPaidAndIssueCertificate($request->user()->id);

        ActivityLog::record('payments.confirmed', "Confirmed certificate payment {$payment->reference} ({$payment->currency} {$payment->amount})", $payment);

        return back()->with('status', 'Payment confirmed and certificate issued.');
    }

    public function reject(string $tenant, CertificatePayment $payment): RedirectResponse
    {
        abort_unless($payment->isPending(), 404);

        $payment->update(['status' => PaymentStatus::Cancelled->value]);

        ActivityLog::record('payments.rejected', "Rejected certificate payment {$payment->reference} ({$payment->currency} {$payment->amount})", $payment);

        return back()->with('status', 'Payment marked as cancelled.');
    }
}
