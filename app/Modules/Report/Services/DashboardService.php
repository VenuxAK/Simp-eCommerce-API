<?php

namespace App\Modules\Report\Services;

use App\Modules\Cash\Repositories\CashSessionRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Repositories\OrderRepository;

class DashboardService
{
    use StoreScope;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly CashSessionRepository $cashSessionRepository,
    ) {}

    public function summary(): array
    {
        $storeId = $this->resolveStoreId();

        $todayOrders = $this->orderRepository->findTodayOrdersByStore($storeId);
        $todaySales = (float) $todayOrders->sum('total_amount');
        $todayOrderCount = $todayOrders->count();

        $recentOrders = $this->orderRepository->findRecentOrdersByStore($storeId, 10);

        $productIds = $this->productRepository->getIdsByStore($storeId);

        $variants = $this->variantRepository->findStockSummaryByProductIds($productIds->toArray());

        $lowStockVariants = $variants->where('stock_status', 'low');
        $outOfStockVariants = $variants->where('stock_status', 'out');
        $wellStockedCount = $variants->where('stock_status', 'ok')->count();

        $activeSession = $this->cashSessionRepository->findActiveByStore($storeId);

        return [
            'today_sales' => $todaySales,
            'today_orders_count' => $todayOrderCount,
            'active_session' => $activeSession,
            'total_products' => $productIds->count(),
            'total_variants' => $variants->count(),
            'low_stock_count' => $lowStockVariants->count(),
            'out_of_stock_count' => $outOfStockVariants->count(),
            'low_stock_variants' => $lowStockVariants->take(5)->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'product' => $v->product->name,
                'size' => $v->size,
                'color' => $v->color,
                'stock' => $v->stock_quantity,
            ]),
            'recent_orders' => $recentOrders,
        ];
    }
}
