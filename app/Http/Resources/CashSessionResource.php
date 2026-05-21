<?php

namespace App\Http\Resources;

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
            'closing_balance' => $this->closing_balance ? (float) $this->closing_balance : null,
            'expected_balance' => $this->expected_balance ? (float) $this->expected_balance : null,
            'difference' => $this->difference ? (float) $this->difference : null,
            'notes' => $this->notes,
            'is_open' => $this->closed_at === null,
        ];
    }
}
