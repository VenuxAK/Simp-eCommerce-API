<?php

namespace App\Modules\Report\Services;

use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Sales\Models\Payment;

class ReportService
{
    use StoreScope;

    public function sales(string $dateFrom, string $dateTo): array
    {
        $orders = Order::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->where('status', 'completed');
        $this->scopeByStore($orders);
        $orders = $orders->get();

        $totalSales = (float) $orders->sum('total_amount');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $itemsSold = OrderItem::whereHas('order', function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->where('status', 'completed');
            $this->scopeByStore($q);
        })->sum('quantity');

        $dailySales = $orders->groupBy(fn ($o) => $o->created_at->toDateString())
            ->map(fn ($dayOrders) => [
                'date' => $dayOrders->first()->created_at->toDateString(),
                'total' => (float) $dayOrders->sum('total_amount'),
                'count' => $dayOrders->count(),
            ])
            ->values();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'average_order_value' => round($averageOrderValue, 2),
            'items_sold' => $itemsSold,
            'daily_breakdown' => $dailySales,
        ];
    }

    public function bestSellers(string $dateFrom, string $dateTo, int $limit): array
    {
        $items = OrderItem::selectRaw('
                product_variant_id,
                SUM(quantity) as total_qty,
                SUM(subtotal) as total_revenue
            ')
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                    ->where('status', 'completed');
                $this->scopeByStore($q);
            })
            ->groupBy('product_variant_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        $items->load(['variant.product.category']);

        return $items->map(fn ($item) => [
            'product_variant_id' => $item->product_variant_id,
            'product_name' => $item->variant?->product?->name ?? 'Deleted',
            'category' => $item->variant?->product?->category?->name ?? '',
            'size' => $item->variant?->size,
            'color' => $item->variant?->color,
            'sku' => $item->variant?->sku,
            'total_qty' => (int) $item->total_qty,
            'total_revenue' => (float) $item->total_revenue,
        ])->toArray();
    }

    public function paymentMethods(string $dateFrom, string $dateTo): array
    {
        $methods = Payment::selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                    ->where('status', 'completed');
                $this->scopeByStore($q);
            })
            ->groupBy('method')
            ->get();

        return $methods->map(fn ($m) => [
            'method' => $m->method,
            'count' => (int) $m->count,
            'total' => (float) $m->total,
        ])->toArray();
    }
}
