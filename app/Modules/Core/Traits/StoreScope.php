<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait StoreScope
{
    public function scopeByStore(Builder $query): Builder
    {
        $user = request()->user();

        if ($user && ($user->isStaff() || $user->isStoreAdmin()) && $user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        return $query;
    }

    public function mergeStoreId(array $data): array
    {
        $user = request()->user();

        if ($user && ($user->isStaff() || $user->isStoreAdmin()) && $user->store_id) {
            $data['store_id'] = $user->store_id;
        }

        return $data;
    }
}
