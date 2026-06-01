<?php

namespace App\Modules\ECommerce\Http\Resources;

use App\Modules\Customer\Http\Resources\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'address_id' => $this->address_id,
            'address' => new AddressResource($this->whenLoaded('address')),
            'method' => $this->method,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'notes' => $this->notes,
        ];
    }
}
