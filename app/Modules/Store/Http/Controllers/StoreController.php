<?php

namespace App\Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Store\Http\Requests\StoreStoreRequest;
use App\Modules\Store\Http\Requests\UpdateStoreRequest;
use App\Modules\Store\Http\Resources\StoreResource;
use App\Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles Store-related API requests.
 */
class StoreController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $stores = Store::orderBy('name')->paginate(20);

        return StoreResource::collection($stores);
    }

    public function store(StoreStoreRequest $request): JsonResponse
    {
        $store = Store::create($request->validated());

        return (new StoreResource($store))->response()->setStatusCode(201);
    }

    public function show(Store $store): StoreResource
    {
        return new StoreResource($store);
    }

    public function update(UpdateStoreRequest $request, Store $store): StoreResource
    {
        $store->update($request->validated());

        return new StoreResource($store);
    }

    public function destroy(Store $store): JsonResponse
    {
        if ($store->slug === 'main') {
            return $this->respondError('Cannot delete the main store.');
        }
        $store->delete();

        return $this->respondMessage('Store deleted.');
    }
}
