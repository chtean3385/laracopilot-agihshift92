<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class InvoicePdf
{
    public static function generate(Invoice $invoice): ?string
    {
        try {
            $invoice->loadMissing([
                'booking.room',
                'booking.extraCharges',
                'customer',
            ]);

            $settings = Setting::where('hotel_id', $invoice->hotel_id)->first();

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice'  => $invoice,
                'settings' => $settings,
            ]);

            $pdf->setPaper('A4', 'portrait');

            return $pdf->output();
        } catch (\Throwable $e) {
            Log::error('InvoicePdf::generate failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id ?? null,
                'trace'      => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
