<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;

class InvoiceNumberGenerator
{
    public function generate(): string
    {
        return $this->generateNumber(Invoice::class, 'invoice_number', 'INV');
    }

    public function generateOrderNumber(): string
    {
        return $this->generateNumber(Order::class, 'order_number', 'ORD');
    }

    private function generateNumber(string $modelClass, string $column, string $prefix): string
    {
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $last = $modelClass::where($column, 'like', $pattern)
            ->orderBy($column, 'desc')
            ->first();

        $newNumber = $last ? ((int) substr($last->$column, -4)) + 1 : 1;

        return "{$prefix}-{$date}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
