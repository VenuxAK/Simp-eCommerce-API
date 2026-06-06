<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Sales\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates Eloquent data access for Payment records.
 *
 * Provides aggregation queries for payment-method reporting
 * used by the dashboard and report modules.
 *
 * @extends Repository<Payment>
 */
class PaymentRepository extends Repository
{
    protected function model(): string
    {
        return Payment::class;
    }

    /**
     * Return per-method totals and counts within a date range.
     *
     * Each row includes `method`, `count`, and `total`.
     * Only payments belonging to completed orders are counted.
     * Optionally scoped to a store.
     */
    public function getMethodSummary(string $from, string $to, ?int $storeId = null): Collection
    {
        return Payment::selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->whereHas('order', function (Builder $q) use ($from, $to, $storeId) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                    ->where('status', 'completed')
                    ->when($storeId, fn (Builder $sq) => $sq->where('store_id', $storeId));
            })
            ->groupBy('method')
            ->get();
    }
}
