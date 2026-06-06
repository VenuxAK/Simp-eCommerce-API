<?php

namespace App\Modules\Report\Services;

use App\Modules\Cash\Models\CashSession;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Models\Order;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    use StoreScope;

    public function summary(): array
    {
        $today = now()->startOfDay();

        $orderQuery = Order::where('created_at', '>=', $today);
        $this->scopeByStore($orderQuery);
        $todaySales = (float) $orderQuery->sum('total_amount');
        $todayOrderCount = $orderQuery->count();

        $recentOrders = Order::with(['user', 'customer', 'items.variant', 'payment'])
            ->orderBy('created_at', 'desc');
        $this->scopeByStore($recentOrders);
        $recentOrders = $recentOrders->limit(10)->get();

        $productIds = Product::query();
        $this->scopeByStore($productIds);
        $productIds = $productIds->pluck('id');

        $variants = ProductVariant::with('product')
            ->whereIn('product_id', $productIds)
            ->select('id', 'product_id', 'sku', 'size', 'color', 'stock_quantity',
                DB::raw("CASE WHEN stock_quantity = 0 THEN 'out' WHEN stock_quantity <= 5 THEN 'low' ELSE 'ok' END as stock_status"))
            ->get();

        $lowStockVariants = $variants->where('stock_status', 'low');
        $outOfStockVariants = $variants->where('stock_status', 'out');
        $wellStockedCount = $variants->where('stock_status', 'ok')->count();

        $activeSession = CashSession::whereNull('closed_at');
        $this->scopeByStore($activeSession);
        $activeSession = $activeSession->first();

        return [
            'today_sales' => $todaySales,
            'today_orders_count' => $todayOrderCount,
            'active_session' => $activeSession,
            'total_products' => $productIds->count(),
            'total_variants' => $variants->count(),
            'low_stock_count' => $lowStockVariants->count(),
            'out_of_stock_count' => $outOfStockVariants->count(),
            'low_stock_variants' => $lowStockVariants->map(fn ($v) => [
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
