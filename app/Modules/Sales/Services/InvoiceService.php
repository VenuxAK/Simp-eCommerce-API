<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\Invoice;

class InvoiceService
{
    public function getShopMetadata(): array
    {
        if (app()->bound('current_store') && ($store = app('current_store'))) {
            return [
                'shop_name' => $store->name,
                'shop_address' => $store->description ?? $store->name,
                'shop_phone' => $store->phone ?? 'N/A',
            ];
        }

        return [
            'shop_name' => 'SimpCommerce',
            'shop_address' => 'Home Store',
            'shop_phone' => 'N/A',
        ];
    }

    public function loadForPrint(Invoice $invoice): Invoice
    {
        return $invoice->load(['order.user', 'order.customer', 'order.items.variant.product', 'order.payment']);
    }

    public function loadForReceipt(Invoice $invoice): Invoice
    {
        return $invoice->load(['order.customer', 'order.items.variant.product']);
    }

    public function loadForPdf(Invoice $invoice): Invoice
    {
        return $invoice->load(['order.customer', 'order.items.variant.product', 'order.payment']);
    }
}
