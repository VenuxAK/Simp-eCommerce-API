<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Encapsulates Eloquent data access for Invoice records.
 *
 * Provides filtered/paginated listings with support for the
 * deep relation loads needed by print, receipt, and PDF flows.
 *
 * @extends Repository<Invoice>
 */
class InvoiceRepository extends Repository
{
    protected function model(): string
    {
        return Invoice::class;
    }

    /**
     * Find an invoice by ID with the given relations loaded.
     */
    public function findWithRelations(int $id, array $relations): ?Invoice
    {
        return Invoice::with($relations)->find($id);
    }

    /**
     * Paginate invoices filtered by optional status and date range.
     *
     * Supported filter keys:
     * - `status`      Exact match on the status column.
     * - `date_from`   Start of the date range (inclusive) on issued_date.
     * - `date_to`     End of the date range (inclusive) on issued_date.
     *
     * Results are ordered by newest first.
     */
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::with(['order.user', 'order.customer', 'order.items.variant.product']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('issued_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('issued_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
