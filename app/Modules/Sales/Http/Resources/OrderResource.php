<?php

namespace App\Modules\Sales\Http\Resources;

use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\ECommerce\Http\Resources\ShipmentResource;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Order model into a JSON response.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'order_number' => $this->order_number,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'source' => $this->source,
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'shipment' => new ShipmentResource($this->whenLoaded('shipment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
