<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait QueryFilter
{
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $param => $column) {
            $query->when(request($param), fn($q) => $q->where($column, request($param)));
        }
        return $query;
    }

    protected function applyDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        $query->when(request('date_from'), fn($q) => $q->whereDate($column, '>=', request('date_from')));
        $query->when(request('date_to'), fn($q) => $q->whereDate($column, '<=', request('date_to')));
        return $query;
    }

    protected function latestPaginated(Builder $query, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
