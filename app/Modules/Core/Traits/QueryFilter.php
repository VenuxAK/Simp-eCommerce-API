<?php

namespace App\Modules\Core\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lightweight query filtering helpers for controllers that need basic
 * equality filtering and date-range scoping without a full query builder package.
 */
trait QueryFilter
{
    /**
     * Apply exact-match filters from request query params.
     *
     * $filters maps request parameter names to database columns.
     * Only applies the WHERE clause if the param is present in the request.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $param => $column) {
            $query->when(request($param), fn ($q) => $q->where($column, request($param)));
        }

        return $query;
    }

    /**
     * Constrain results between optional date_from / date_to request params.
     *
     * Both boundaries are inclusive. Works with any date/datetime column.
     */
    protected function applyDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        $query->when(request('date_from'), fn ($q) => $q->whereDate($column, '>=', request('date_from')));
        $query->when(request('date_to'), fn ($q) => $q->whereDate($column, '<=', request('date_to')));

        return $query;
    }

    protected function latestPaginated(Builder $query, int $perPage = 20): LengthAwarePaginator
    {
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
