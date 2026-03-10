<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\PaymentLinkConfig;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentLinksController extends Controller
{
    private function requireModule()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!Module::isEnabled('payment_links')) abort(403, 'Payment Links module is not enabled.');
        return null;
    }

    public function config()
    {
        if ($r = $this->requireModule()) return $r;
        $config = PaymentLinkConfig::getConfig();
        return view('admin.payment_links.config', compact('config'));
    }

    public function configSave(Request $request)
    {
        if ($r = $this->requireModule()) return $r;
        $data = $request->validate([
            'upi_id'             => 'nullable|string|max:100',
            'upi_name'           => 'nullable|string|max:100',
            'upi_enabled'        => 'nullable|boolean',
            'razorpay_key_id'    => 'nullable|string|max:200',
            'razorpay_key_secret'=> 'nullable|string|max:200',
            'razorpay_enabled'   => 'nullable|boolean',
        ]);
        $data['upi_enabled']       = $request->boolean('upi_enabled');
        $data['razorpay_enabled']  = $request->boolean('razorpay_enabled');

        $config = PaymentLinkConfig::getConfig();
        $config->update($data);
        ActivityLogger::log('Updated', 'Payment Links', 'Payment Links configuration saved.');
        return redirect()->route('payment_links.config')->with('success', 'Configuration saved!');
    }

    public function razorpayCreate(Request $request, $invoiceId)
    {
        if ($r = $this->requireModule()) return $r;
        $invoice = Invoice::with(['booking.room', 'customer', 'booking.payments'])->findOrFail($invoiceId);
        $config  = PaymentLinkConfig::getConfig();

        if (!$config->razorpay_enabled || !$config->razorpay_key_id || !$config->razorpay_key_secret) {
            return response()->json(['error' => 'Razorpay is not configured or disabled.'], 422);
        }

        $gst          = $invoice->total_amount * (config('app.tax_rate', 0) / 100);
        $grand        = $invoice->total_amount;
        $amountPaisa  = (int) round($grand * 100);

        if ($amountPaisa <= 0) {
            return response()->json(['error' => 'Invoice is already fully paid.'], 422);
        }

        $payload = [
            'amount'      => $amountPaisa,
            'currency'    => 'INR',
            'accept_partial' => false,
            'description' => 'Invoice ' . $invoice->invoice_number . ' — ' . ($invoice->customer->name ?? 'Guest'),
            'customer'    => [
                'name'  => $invoice->customer->name ?? 'Guest',
                'email' => $invoice->customer->email ?? null,
                'contact' => $invoice->customer->phone ?? null,
            ],
            'notify'      => ['sms' => false, 'email' => false],
            'reminder_enable' => false,
            'notes'       => [
                'invoice_id'     => (string) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'booking_number' => $invoice->booking->booking_number ?? '',
            ],
            'callback_url'    => route('payment_links.razorpay.webhook'),
            'callback_method' => 'get',
        ];

        $response = Http::withBasicAuth($config->razorpay_key_id, $config->razorpay_key_secret)
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        if ($response->failed()) {
            $err = $response->json('error.description') ?? 'Razorpay API error.';
            return response()->json(['error' => $err], 422);
        }

        $data = $response->json();
        $invoice->update([
            'razorpay_payment_link_id'     => $data['id'],
            'razorpay_payment_link_url'    => $data['short_url'],
            'razorpay_payment_link_status' => $data['status'],
        ]);

        ActivityLogger::log('Created', 'Payment Link', 'Razorpay link created for Invoice #' . $invoice->invoice_number . ': ' . $data['short_url']);

        return response()->json([
            'link' => $data['short_url'],
            'id'   => $data['id'],
        ]);
    }

    public function razorpayWebhook(Request $request)
    {
        $linkId    = $request->input('razorpay_payment_link_id');
        $paymentId = $request->input('razorpay_payment_id');
        $status    = $request->input('razorpay_payment_link_status');

        if (!$linkId) return response('Missing data', 400);

        $invoice = Invoice::where('razorpay_payment_link_id', $linkId)->first();
        if (!$invoice) return response('Invoice not found', 404);

        $invoice->update(['razorpay_payment_link_status' => $status]);

        if ($status === 'paid' && $paymentId) {
            $config     = PaymentLinkConfig::getConfig();
            $gst        = $invoice->total_amount * (0 / 100);
            $grand      = $invoice->total_amount;
            $balance    = max(0, $grand - $invoice->paid_amount);

            if ($balance > 0) {
                Payment::create([
                    'booking_id'     => $invoice->booking_id,
                    'amount'         => $balance,
                    'payment_method' => 'razorpay',
                    'payment_date'   => now()->toDateString(),
                    'reference'      => $paymentId,
                    'notes'          => 'Auto-recorded via Razorpay Payment Link',
                ]);
                $invoice->update([
                    'paid_amount' => $invoice->paid_amount + $balance,
                    'status'      => 'paid',
                ]);
                ActivityLogger::log('Payment', 'Razorpay', 'Auto-payment recorded for Invoice #' . $invoice->invoice_number . ' via Razorpay (' . $paymentId . ')');
            }
        }

        return response('OK', 200);
    }

    public function upiQr($invoiceId)
    {
        if ($r = $this->requireModule()) return $r;
        $invoice = Invoice::with(['booking', 'customer'])->findOrFail($invoiceId);
        $config  = PaymentLinkConfig::getConfig();

        if (!$config->upi_enabled || !$config->upi_id) {
            return response()->json(['error' => 'UPI is not configured or disabled.'], 422);
        }

        $balance  = max(0, $invoice->total_amount - $invoice->paid_amount);
        $upiUrl   = 'upi://pay?pa=' . urlencode($config->upi_id)
                  . '&pn=' . urlencode($config->upi_name ?? 'Resort')
                  . '&am=' . number_format($balance, 2, '.', '')
                  . '&cu=INR'
                  . '&tn=' . urlencode('Invoice ' . $invoice->invoice_number);

        $qrApiUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($upiUrl) . '&choe=UTF-8';

        return response()->json([
            'qr_url'    => $qrApiUrl,
            'upi_url'   => $upiUrl,
            'upi_id'    => $config->upi_id,
            'upi_name'  => $config->upi_name,
            'amount'    => $balance,
        ]);
    }

    public function upiConfig()
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);
        if (!Module::isEnabled('payment_links')) return response()->json(['error' => 'Module disabled'], 403);
        $config = PaymentLinkConfig::getConfig();
        if (!$config->upi_enabled || !$config->upi_id) {
            return response()->json(['error' => 'UPI is not configured. Go to Automation → Payment Links to add your UPI ID.'], 422);
        }
        return response()->json([
            'upi_id'   => $config->upi_id,
            'upi_name' => $config->upi_name ?? 'Resort',
        ]);
    }

    public function razorpayForBooking(Request $request, $bookingId)
    {
        if ($r = $this->requireModule()) return $r;
        $booking = \App\Models\Booking::with('customer', 'room')->findOrFail($bookingId);
        $config  = PaymentLinkConfig::getConfig();

        if (!$config->razorpay_enabled || !$config->razorpay_key_id || !$config->razorpay_key_secret) {
            return response()->json(['error' => 'Razorpay is not configured or disabled.'], 422);
        }

        $amount      = (float) $request->input('amount', 0);
        $note        = $request->input('note', 'Advance Payment');
        $amountPaisa = (int) round($amount * 100);

        if ($amountPaisa <= 0) {
            return response()->json(['error' => 'Amount must be greater than zero.'], 422);
        }

        $payload = [
            'amount'          => $amountPaisa,
            'currency'        => 'INR',
            'accept_partial'  => false,
            'description'     => $note . ' — ' . $booking->booking_number,
            'customer'        => [
                'name'    => $booking->customer->name ?? 'Guest',
                'email'   => $booking->customer->email ?? null,
                'contact' => $booking->customer->phone ?? null,
            ],
            'notify'          => ['sms' => false, 'email' => false],
            'reminder_enable' => false,
            'notes'           => [
                'booking_id'     => (string) $booking->id,
                'booking_number' => $booking->booking_number,
            ],
        ];

        $response = Http::withBasicAuth($config->razorpay_key_id, $config->razorpay_key_secret)
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        if ($response->failed()) {
            $err = $response->json('error.description') ?? 'Razorpay API error.';
            return response()->json(['error' => $err], 422);
        }

        $data = $response->json();
        ActivityLogger::log('Created', 'Payment Link', 'Razorpay advance link created for Booking #' . $booking->booking_number . ': ' . $data['short_url']);

        return response()->json([
            'link' => $data['short_url'],
            'id'   => $data['id'],
        ]);
    }
}
