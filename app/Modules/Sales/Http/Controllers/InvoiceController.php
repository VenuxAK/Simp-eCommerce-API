<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Modules\Sales\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Handles Invoice-related API requests.
 */
class InvoiceController extends Controller
{
    use ApiResponse, QueryFilter;

    public function index(): AnonymousResourceCollection
    {
        $invoices = $this->applyFilters(
            Invoice::with(['order.user', 'order.customer', 'order.items.variant.product']),
            ['status' => 'status'],
        );
        $invoices = $this->applyDateRange($invoices, 'issued_date');
        $invoices = $this->latestPaginated($invoices);

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
            'shop_name' => 'SimpCommerce',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);
    }

    public function receipt(Invoice $invoice): View
    {
        $invoice->load(['order.customer', 'order.items.variant.product']);

        return view('pdf.receipt-thermal', [
            'invoice' => $invoice,
            'shop_name' => 'SimpCommerce',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['order.customer', 'order.items.variant.product', 'order.payment']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'shop_name' => 'SimpCommerce',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
