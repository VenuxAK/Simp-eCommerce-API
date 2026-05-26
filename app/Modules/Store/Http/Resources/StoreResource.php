<?php

namespace App\Modules\Store\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Store model into a JSON response.
 */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'description' => $this->description,
            'logo' => $this->logo ? url('storage/' . $this->logo) : null,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
