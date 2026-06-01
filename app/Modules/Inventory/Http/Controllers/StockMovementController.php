<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Inventory\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles StockMovement-related API requests.
 */
class StockMovementController extends Controller
{
    use QueryFilter, StoreScope;

    public function index(): AnonymousResourceCollection
    {
        $query = StockMovement::with(['variant.product', 'user']);
        $this->scopeByStore($query);
        $movements = $this->applyFilters(
            $query,
            ['variant_id' => 'product_variant_id', 'reason' => 'reason'],
        );
        $movements = $this->applyDateRange($movements);
        $movements = $this->latestPaginated($movements);

        return StockMovementResource::collection($movements);
    }
}
