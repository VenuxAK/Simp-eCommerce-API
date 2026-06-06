<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Customer-facing order history.
 *
 * Only returns orders placed by the authenticated customer
 * and only online orders (source=online).
 */
class MyOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->where('source', 'online')
            ->with(['items.variant.product', 'shipment', 'invoice', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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

        if ($order->status !== 'processing') {
            return $this->respondError('Only orders in processing status can be cancelled.', 422);
        }

        DB::transaction(function () use ($order) {
            // Restock all items.
            foreach ($order->items as $item) {
                $item->variant->increment('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            if ($order->invoice) {
                $order->invoice->update(['status' => 'cancelled']);
            }
        });

        return $this->respondMessage('Order cancelled.');
    }
}
