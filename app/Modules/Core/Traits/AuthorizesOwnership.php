<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait AuthorizesOwnership
{
    public function authorizeOwnership(Request $request, Model $model, string $foreignKey = 'customer_id'): void
    {
        if ($model->getAttribute($foreignKey) !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
