<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Sales\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates Eloquent data access for OrderItem records.
 *
 * Provides aggregation queries for sales reporting, such as
 * total sold quantities and best-selling variants.
 *
 * @extends Repository<OrderItem>
 */
class OrderItemRepository extends Repository
{
    protected function model(): string
    {
        return OrderItem::class;
    }

    /**
     * Return the total quantity of items sold within a date range.
     *
     * Only completed orders are counted. Optionally scoped to a store.
     */
    public function getSoldQuantityBetween(string $from, string $to, ?int $storeId = null): int
    {
        return (int) OrderItem::whereHas('order', function (Builder $q) use ($from, $to, $storeId) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->where('status', 'completed')
                ->when($storeId, fn (Builder $sq) => $sq->where('store_id', $storeId));
        })->sum('quantity');
    }

    /**
     * Return the top-selling product variants within a date range.
     *
     * Each row includes `product_variant_id`, `total_qty`, and `total_revenue`.
     * Only completed orders are considered. Optionally scoped to a store.
     */
    public function getBestSellers(string $from, string $to, ?int $storeId = null, int $limit = 20): Collection
    {
        return OrderItem::selectRaw('
                product_variant_id,
                SUM(quantity) as total_qty,
                SUM(subtotal) as total_revenue
            ')
            ->whereHas('order', function (Builder $q) use ($from, $to, $storeId) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                    ->where('status', 'completed')
                    ->when($storeId, fn (Builder $sq) => $sq->where('store_id', $storeId));
            })
            ->groupBy('product_variant_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }
}
