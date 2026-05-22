<?php

namespace App\Modules\Customer\Models;

use App\Modules\Sales\Models\Order;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a Customer in the system.
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'address', 'loyalty_points'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    // TODO: Replace with contract when Sales module is extracted.
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
