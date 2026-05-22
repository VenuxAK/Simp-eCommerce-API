<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $redactFields = ['password', 'remember_token', 'credit_card', 'card_number', 'cvv'];

        $oldValues = $this->old_values ? $this->redactSensitive($this->old_values, $redactFields) : null;
        $newValues = $this->new_values ? $this->redactSensitive($this->new_values, $redactFields) : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'action' => $this->action,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function redactSensitive(mixed $values, array $sensitiveFields): mixed
    {
        if (!is_array($values)) {
            $decoded = json_decode($values, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $values;
            }
            $values = $decoded;
        }

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }
}
