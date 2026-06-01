<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Http\Resources\CashSessionResource;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse, StoreScope;

    public function summary(): JsonResponse
    {
        $today = now()->startOfDay();

        $orderQuery = Order::where('created_at', '>=', $today);
        $this->scopeByStore($orderQuery);
        $todayOrders = $orderQuery;
        $todaySales = (float) $todayOrders->sum('total_amount');
        $todayOrderCount = $todayOrders->count();

        $recentOrders = Order::with(['user', 'customer', 'items.variant', 'payment'])
            ->orderBy('created_at', 'desc');
        $this->scopeByStore($recentOrders);
        $recentOrders = $recentOrders->limit(10)->get();

        $productIds = Product::query();
        $this->scopeByStore($productIds);
        $productIds = $productIds->pluck('id');

        $lowStockVariants = ProductVariant::with('product')
            ->whereIn('product_id', $productIds)
            ->where('stock_quantity', '<=', 5)
            ->where('stock_quantity', '>', 0)
            ->get();

        $outOfStockVariants = ProductVariant::with('product')
            ->whereIn('product_id', $productIds)
            ->where('stock_quantity', 0)
            ->get();

        $activeSession = CashSession::whereNull('closed_at');
        $this->scopeByStore($activeSession);
        $activeSession = $activeSession->first();

        return $this->respond([
            'today_sales' => $todaySales,
            'today_orders_count' => $todayOrderCount,
            'active_session' => $activeSession ? new CashSessionResource($activeSession) : null,
            'total_products' => $productIds->count(),
            'total_variants' => $lowStockVariants->count() + $outOfStockVariants->count() + ProductVariant::whereIn('product_id', $productIds)->where('stock_quantity', '>', 5)->count(),
            'low_stock_count' => $lowStockVariants->count(),
            'out_of_stock_count' => $outOfStockVariants->count(),
            'low_stock_variants' => $lowStockVariants->map(fn($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'product' => $v->product->name,
                'size' => $v->size,
                'color' => $v->color,
                'stock' => $v->stock_quantity,
            ]),
            'recent_orders' => OrderResource::collection($recentOrders),
        ]);
    }
}
