<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'invoice_number' => $this->invoice_number,
            'issued_date' => $this->issued_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
