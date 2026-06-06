<?php

namespace App\Modules\Report\Services;

use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Repositories\OrderItemRepository;
use App\Modules\Sales\Repositories\OrderRepository;
use App\Modules\Sales\Repositories\PaymentRepository;

class ReportService
{
    use StoreScope;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly PaymentRepository $paymentRepository,
    ) {}

    public function sales(string $dateFrom, string $dateTo): array
    {
        $storeId = $this->resolveStoreId();

        $orders = $this->orderRepository->findCompletedBetween($dateFrom, $dateTo, $storeId);

        $totalSales = (float) $orders->sum('total_amount');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $itemsSold = $this->orderItemRepository->getSoldQuantityBetween($dateFrom, $dateTo, $storeId);

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
        $storeId = $this->resolveStoreId();

        $items = $this->orderItemRepository->getBestSellers($dateFrom, $dateTo, $storeId, $limit);

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
        $storeId = $this->resolveStoreId();

        $methods = $this->paymentRepository->getMethodSummary($dateFrom, $dateTo, $storeId);

        return $methods->map(fn ($m) => [
            'method' => $m->method,
            'count' => (int) $m->count,
            'total' => (float) $m->total,
        ])->toArray();
    }
}
