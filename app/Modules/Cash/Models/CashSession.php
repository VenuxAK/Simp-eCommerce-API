<?php

namespace App\Modules\Cash\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Store;
use Database\Factories\CashSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a CashSession in the system.
 */
class CashSession extends Model
{
    /** @use HasFactory<CashSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'store_id', 'opened_at', 'closed_at',
        'opening_balance', 'closing_balance',
        'expected_balance', 'difference', 'notes',
    ];

    protected static function newFactory(): CashSessionFactory
    {
        return CashSessionFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }
}
