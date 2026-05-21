<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $movements = StockMovement::with(['variant.product', 'user'])
            ->when(request('variant_id'), fn($q) => $q->where('product_variant_id', request('variant_id')))
            ->when(request('reason'), fn($q) => $q->where('reason', request('reason')))
            ->when(request('date_from'), fn($q) => $q->whereDate('created_at', '>=', request('date_from')))
            ->when(request('date_to'), fn($q) => $q->whereDate('created_at', '<=', request('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return StockMovementResource::collection($movements);
    }
}
