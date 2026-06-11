<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Core\Repositories\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandRepository extends Repository
{
    protected function model(): string
    {
        return Brand::class;
    }

    public function paginateByStore(int $storeId, int $perPage = 20): LengthAwarePaginator
    {
        return Brand::where('store_id', $storeId)
            ->withCount('products')
            ->orderBy('name')
            ->paginate($this->clampPerPage($perPage));
    }

    public function getProductCount(int $id): int
    {
        return Brand::withCount('products')->find($id)?->products_count ?? 0;
    }
}
