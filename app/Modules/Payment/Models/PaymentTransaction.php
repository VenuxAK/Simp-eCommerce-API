<?php

namespace App\Modules\Payment\Models;

use App\Modules\Sales\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks individual payment gateway transactions for audit and reconciliation.
 */
class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'payment_id', 'order_id',
        'gateway', 'transaction_id', 'gateway_status',
        'amount', 'currency',
        'request_data', 'response_data',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_data' => 'array',
            'response_data' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\Payment::class);
    }
}
