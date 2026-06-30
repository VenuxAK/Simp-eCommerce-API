<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\OrderStatus;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\PaginatesResults;
use App\Modules\ECommerce\Services\MyOrderService;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Customer-facing order history.
 *
 * Only returns orders placed by the authenticated customer
 * and only online orders (source=online).
 */
class MyOrderController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function __construct(
        private readonly MyOrderService $myOrderService,
        private readonly OrderRepository $orderRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $this->orderRepository->query()
            ->where('customer_id', $request->user()->id)
            ->where('source', 'online')
            ->with(['items.variant.product', 'shipment', 'invoice', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->resolvePerPage());

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        if ($order->customer_id !== $request->user()->id) {
            abort(403);
        }

        return new OrderResource($order->load([
            'items.variant.product', 'shipment.address', 'invoice', 'payment',
        ]));
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->customer_id !== $request->user()->id) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Processing) {
            return $this->respondError(__('messages.orders.cancel_not_allowed'), 422);
        }

        $this->myOrderService->cancelOrder($order);

        return $this->respondMessage('Order cancelled.');
    }
}
