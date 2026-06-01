<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Inventory\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    use QueryFilter;

    public function index(): AnonymousResourceCollection
    {
        $query = StockMovement::with(['variant.product', 'user']);

        $user = request()->user();
        if ($user && ($user->isStaff() || $user->isStoreAdmin()) && $user->store_id) {
            $query->whereHas('variant.product', fn($q) => $q->where('store_id', $user->store_id));
        } elseif ($user && $user->isRoot() && request()->header('X-Store')) {
            $store = \App\Modules\Store\Models\Store::where('slug', request()->header('X-Store'))->first();
            if ($store) {
                $query->whereHas('variant.product', fn($q) => $q->where('store_id', $store->id));
            }
        }

        $movements = $this->applyFilters(
            $query,
            ['variant_id' => 'product_variant_id', 'reason' => 'reason'],
        );
        $movements = $this->applyDateRange($movements);
        $movements = $this->latestPaginated($movements);

        return StockMovementResource::collection($movements);
    }
}
