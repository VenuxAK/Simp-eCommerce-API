<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transaction_id,
            'gateway_status' => $this->gateway_status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
