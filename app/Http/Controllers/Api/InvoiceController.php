<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    use ApiResponse;
    public function index(): AnonymousResourceCollection
    {
        $invoices = Invoice::with(['order.user', 'order.customer', 'order.items.variant.product'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('date_from'), fn($q) => $q->whereDate('issued_date', '>=', request('date_from')))
            ->when(request('date_to'), fn($q) => $q->whereDate('issued_date', '<=', request('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['order.user', 'order.customer', 'order.items.variant.product', 'order.payment']));
    }

    public function print(Invoice $invoice): JsonResponse
    {
        $invoice->load(['order.user', 'order.customer', 'order.items.variant.product', 'order.payment']);

        return $this->respond([
            'invoice' => new InvoiceResource($invoice),
            'shop_name' => 'SimpPOS',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);
    }

    public function receipt(Invoice $invoice): \Illuminate\View\View
    {
        $invoice->load(['order.customer', 'order.items.variant.product']);

        return view('pdf.receipt-thermal', [
            'invoice' => $invoice,
            'shop_name' => 'SimpPOS',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);
    }

    public function pdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $invoice->load(['order.customer', 'order.items.variant.product', 'order.payment']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'shop_name' => 'SimpPOS',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
