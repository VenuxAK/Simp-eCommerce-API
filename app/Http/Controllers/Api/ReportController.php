<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    use ApiResponse;
    public function sales(): JsonResponse
    {
        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());

        $orders = Order::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->where('status', 'completed')
            ->get();

        $totalSales = (float) $orders->sum('total_amount');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $itemsSold = OrderItem::whereHas('order', function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->where('status', 'completed');
        })->sum('quantity');

        $dailySales = $orders->groupBy(fn($o) => $o->created_at->toDateString())
            ->map(fn($dayOrders) => [
                'date' => $dayOrders->first()->created_at->toDateString(),
                'total' => (float) $dayOrders->sum('total_amount'),
                'count' => $dayOrders->count(),
            ])
            ->values();

        return $this->respond([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'average_order_value' => round($averageOrderValue, 2),
            'items_sold' => $itemsSold,
            'daily_breakdown' => $dailySales,
        ]);
    }

    public function bestSellers(): JsonResponse
    {
        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());
        $limit = (int) request('limit', 20);

        $items = OrderItem::selectRaw('
                product_variant_id,
                SUM(quantity) as total_qty,
                SUM(subtotal) as total_revenue
            ')
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                    ->where('status', 'completed');
            })
            ->groupBy('product_variant_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        $items->load(['variant.product.category']);

        $data = $items->map(fn($item) => [
            'product_variant_id' => $item->product_variant_id,
            'product_name' => $item->variant?->product?->name ?? 'Deleted',
            'category' => $item->variant?->product?->category?->name ?? '',
            'size' => $item->variant?->size,
            'color' => $item->variant?->color,
            'sku' => $item->variant?->sku,
            'total_qty' => (int) $item->total_qty,
            'total_revenue' => (float) $item->total_revenue,
        ]);

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'data' => $data,
        ]);
    }

    public function paymentMethods(): JsonResponse
    {
        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());

        $methods = Payment::selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                    ->where('status', 'completed');
            })
            ->groupBy('method')
            ->get();

        return $this->respond([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'data' => $methods->map(fn($m) => [
                'method' => $m->method,
                'count' => (int) $m->count,
                'total' => (float) $m->total,
            ]),
        ]);
    }
}
