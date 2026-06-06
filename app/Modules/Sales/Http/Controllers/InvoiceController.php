<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Repositories\InvoiceRepository;
use App\Modules\Sales\Services\InvoiceService;
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

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $filters = request()->only(['status', 'date_from', 'date_to']);

        $invoices = $this->invoiceRepository->paginateFiltered($filters);

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['order.user', 'order.customer', 'order.items.variant.product', 'order.payment']));
    }

    public function print(Invoice $invoice): JsonResponse
    {
        return $this->respond([
            'invoice' => new InvoiceResource($this->invoiceService->loadForPrint($invoice)),
            ...$this->invoiceService->getShopMetadata(),
        ]);
    }

    public function receipt(Invoice $invoice): View
    {
        return view('pdf.receipt-thermal', [
            'invoice' => $this->invoiceService->loadForReceipt($invoice),
            ...$this->invoiceService->getShopMetadata(),
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['order.customer', 'order.items.variant.product', 'order.payment']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            ...$this->invoiceService->getShopMetadata(),
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
