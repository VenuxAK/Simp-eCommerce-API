<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Http\Requests\ToggleWishlistRequest;
use App\Modules\ECommerce\Http\Resources\WishlistItemResource;
use App\Modules\ECommerce\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return WishlistItemResource::collection(
            $this->wishlistService->getItems($request->user()),
        );
    }

    public function toggle(ToggleWishlistRequest $request): JsonResponse
    {
        $result = $this->wishlistService->toggle($request->user(), $request->product_id);

        if (isset($result['item'])) {
            $result['item'] = new WishlistItemResource(
                $result['item']->load('product.category', 'product.variants'),
            );
        }

        return $this->respond($result);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $this->wishlistService->removeItem($request->user(), $id);

        return $this->respondNoContent();
    }

    public function clear(Request $request): JsonResponse
    {
        $this->wishlistService->clear($request->user());

        return $this->respondNoContent();
    }
}
