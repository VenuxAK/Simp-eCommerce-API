<?php

namespace App\Modules\Report\Services;

use App\Modules\Cash\Repositories\CashSessionRepository;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    use StoreScope;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
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

        // Single aggregation query replaces 3 separate variant queries.
        // Computes total/low/out-of-stock counts in one pass using
        // conditional aggregation (CASE inside SUM/COUNT).
        // "Low stock" is defined per-variant via low_stock_threshold;
        // variants with threshold = 0 are never considered low stock.
        $stockSummary = ProductVariant::whereIn('product_id', $productIds->toArray())
            ->select([
                DB::raw('COUNT(*) as total_variants'),
                DB::raw("COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_count"),
                DB::raw("COUNT(CASE WHEN stock_quantity > 0 AND low_stock_threshold > 0 AND stock_quantity <= low_stock_threshold THEN 1 END) as low_stock_count"),
                DB::raw("COUNT(CASE WHEN stock_quantity > COALESCE(NULLIF(low_stock_threshold, 0), 10) THEN 1 END) as well_stocked_count"),
            ])
            ->first();

        // Only fetch the top 5 low-stock variants for the dashboard alert.
        $lowStockVariants = ProductVariant::with('product:id,name')
            ->whereIn('product_id', $productIds->toArray())
            ->where('stock_quantity', '>', 0)
            ->where('low_stock_threshold', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->select('id', 'product_id', 'sku', 'size', 'color', 'stock_quantity', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        $activeSession = $this->cashSessionRepository->findActiveByStore($storeId);

        return [
            'today_sales' => $todaySales,
            'today_orders_count' => $todayOrderCount,
            'active_session' => $activeSession,
            'total_products' => $productIds->count(),
            'total_variants' => (int) ($stockSummary->total_variants ?? 0),
            'low_stock_count' => (int) ($stockSummary->low_stock_count ?? 0),
            'out_of_stock_count' => (int) ($stockSummary->out_of_stock_count ?? 0),
            'low_stock_variants' => $lowStockVariants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'product' => $v->product->name,
                'size' => $v->size,
                'color' => $v->color,
                'stock' => $v->stock_quantity,
                'threshold' => $v->low_stock_threshold,
            ]),
            'recent_orders' => $recentOrders,
        ];
    }
}
