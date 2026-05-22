<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\QueryFilter;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
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
