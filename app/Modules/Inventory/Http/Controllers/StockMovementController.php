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
        $movements = $this->applyFilters(
            StockMovement::with(['variant.product', 'user']),
            ['variant_id' => 'product_variant_id', 'reason' => 'reason'],
        );
        $movements = $this->applyDateRange($movements);
        $movements = $this->latestPaginated($movements);

        return StockMovementResource::collection($movements);
    }
}
