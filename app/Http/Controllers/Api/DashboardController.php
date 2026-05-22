<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Resources\CashSessionResource;
use App\Http\Resources\OrderResource;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;
    public function summary(): JsonResponse
    {
        $today = now()->startOfDay();

        $todayOrders = Order::where('created_at', '>=', $today);
        $todaySales = (float) $todayOrders->sum('total_amount');
        $todayOrderCount = $todayOrders->count();

        $recentOrders = Order::with(['user', 'customer', 'items.variant', 'payment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $lowStockVariants = ProductVariant::with('product')
            ->where('stock_quantity', '<=', 5)
            ->where('stock_quantity', '>', 0)
            ->get();

        $outOfStockVariants = ProductVariant::with('product')
            ->where('stock_quantity', 0)
            ->get();

        $activeSession = CashSession::whereNull('closed_at')->first();

        return $this->respond([
            'today_sales' => $todaySales,
            'today_orders_count' => $todayOrderCount,
            'active_session' => $activeSession ? new CashSessionResource($activeSession) : null,
            'total_products' => Product::count(),
            'total_variants' => ProductVariant::count(),
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
