<?php

namespace App\Http\Resources;

use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'opening_balance' => (float) $this->opening_balance,
            'closing_balance' => (float) $this->closing_balance,
            'expected_balance' => (float) $this->expected_balance,
            'difference' => (float) $this->difference,
            'notes' => $this->notes,
            'is_open' => $this->closed_at === null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
