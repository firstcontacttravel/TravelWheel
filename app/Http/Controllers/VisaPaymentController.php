<?php

namespace App\Http\Controllers;

use App\Models\VisaApplication;
use App\Models\VisaPayment;
use App\Models\VisaQuote;
use App\Services\VisaApplicationDraftService;
use App\Services\VisaFunnelService;
use App\Services\VisaPaymentService;
use App\Services\VisaQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisaPaymentController extends Controller
{
    public function quote(VisaApplication $application, VisaApplicationDraftService $drafts, VisaQuotationService $quotes, VisaFunnelService $funnel): RedirectResponse
    {
        abort_unless($drafts->authorize($application), 403);
        $quote = $quotes->create($application);
        $funnel->record('quote_created', ['quote_reference' => $quote->reference, 'amount' => $quote->payable_total, 'currency' => $quote->checkout_currency], $application, 'quote_created|'.$quote->id);

        return redirect()->route('visa.application', $application);
    }

    public function initialize(VisaQuote $quote, VisaApplicationDraftService $drafts, VisaPaymentService $payments, VisaFunnelService $funnel): RedirectResponse
    {
        abort_unless($drafts->authorize($quote->application), 403);
        $payment = $payments->initialize($quote);
        $funnel->record('payment_started', ['payment_reference' => $payment->reference], $quote->application, 'payment_started|'.$payment->id);

        if ($payment->status === 'paid') {
            return redirect()->route('visa.payments.result', $payment);
        }

        return redirect()->away($payment->checkout_url);
    }

    public function callback(Request $request, VisaPaymentService $payments): RedirectResponse
    {
        $reference = $request->input('paymentReference') ?? $request->input('reference') ?? $request->input('trxref');
        abort_unless($reference, 422, 'Payment reference missing.');
        $payment = $payments->verify($reference, $request->all(), 'callback');

        return redirect()->route('visa.payments.result', $payment);
    }

    public function webhook(Request $request, VisaPaymentService $payments): JsonResponse
    {
        $reference = $request->input('paymentReference') ?? $request->input('data.paymentReference') ?? $request->input('data.payments.paymentReference') ?? $request->input('reference');
        if ($reference && VisaPayment::query()->where('reference', $reference)->exists()) {
            $payments->verify($reference, $request->all(), 'webhook');
        }

        return response()->json(['ackReference' => $request->header('ackReference', $reference ?: (string) \Illuminate\Support\Str::uuid()), 'status' => 'received']);
    }

    public function result(VisaPayment $payment): View
    {
        return view('visa.payment-result', ['payment' => $payment->load('application.product', 'quote')]);
    }
}
