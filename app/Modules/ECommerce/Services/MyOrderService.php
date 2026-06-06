<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Sales\Models\Order;
use Illuminate\Support\Facades\DB;

class MyOrderService
{
    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $item->variant->increment('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            if ($order->invoice) {
                $order->invoice->update(['status' => 'cancelled']);
            }
        });
    }
}
