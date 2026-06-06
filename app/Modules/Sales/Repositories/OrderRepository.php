<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Sales\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates Eloquent data access for Order records.
 *
 * Provides filtered/paginated listings, store-scoped lookups,
 * date-range aggregations, and customer-centric queries.
 *
 * @extends Repository<Order>
 */
class OrderRepository extends Repository
{
    protected function model(): string
    {
        return Order::class;
    }

    /**
     * Find an order by ID with the given relations loaded.
     *
     * Returns null when no matching order exists.
     */
    public function findWithRelations(int $id, array $relations = []): ?Order
    {
        return Order::with($relations)->find($id);
    }

    /**
     * Paginate orders filtered by optional equality constraints and a date range.
     *
     * Supported filter keys:
     * - `status`       Exact match on the status column.
     * - `customer_id`  Exact match on the customer_id column.
     * - `date_from`    Start of the date range (inclusive) on created_at.
     * - `date_to`      End of the date range (inclusive) on created_at.
     *
     * Results are ordered by newest first.
     */
    public function paginateFiltered(array $filters, array $relations = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with($relations);

        foreach (['status', 'customer_id', 'store_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Return all orders placed today, optionally scoped to a store.
     */
    public function findTodayOrdersByStore(?int $storeId): Collection
    {
        return Order::whereDate('created_at', today())
            ->when($storeId, fn (Builder $q) => $q->where('store_id', $storeId))
            ->get();
    }

    /**
     * Return the most recent orders, optionally scoped to a store.
     */
    public function findRecentOrdersByStore(?int $storeId, int $limit = 10): Collection
    {
        return Order::with(['user', 'customer', 'items.variant', 'payment'])
            ->when($storeId, fn (Builder $q) => $q->where('store_id', $storeId))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Return completed orders whose created_at falls within the given date range.
     */
    public function findCompletedBetween(string $from, string $to, ?int $storeId = null): Collection
    {
        return Order::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', 'completed')
            ->when($storeId, fn (Builder $q) => $q->where('store_id', $storeId))
            ->get();
    }

    /**
     * Paginate orders belonging to a specific customer, with optional eager loads.
     */
    public function findByCustomer(int $customerId, array $with = []): LengthAwarePaginator
    {
        return Order::with($with)
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->paginate();
    }
}
