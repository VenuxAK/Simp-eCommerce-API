<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public function generate(): string
    {
        $date = now()->format('Ymd');

        $lastInvoice = Invoice::where('invoice_number', 'like', "INV-{$date}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $newNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return 'INV-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');

        $lastOrder = DB::table('orders')
            ->where('order_number', 'like', "ORD-{$date}-%")
            ->orderBy('order_number', 'desc')
            ->first();

        $newNumber = $lastOrder ? ((int) substr($lastOrder->order_number, -4)) + 1 : 1;

        return 'ORD-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
