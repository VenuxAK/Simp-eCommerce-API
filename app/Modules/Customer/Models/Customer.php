<?php

namespace App\Modules\Customer\Models;

use App\Modules\Customer\Notifications\CustomerPasswordResetNotification;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Models\WishlistItem;
use App\Modules\Sales\Models\Order;
use App\Modules\Store\Models\Store;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

    /**
     * CRM entity that doubles as an e-commerce customer account.
     *
     * password is nullable: walk-in POS customers don't need accounts.
     * Only customers who register through a storefront are authenticatable.
     * store_id links the customer to the store they registered through.
     *
     * Password-reset notifications are dispatched via the Notifiable trait's
     * notify() method, which delegates to a dedicated Notification class.
     * The model does NOT send mail directly — that responsibility belongs
     * to CustomerPasswordResetNotification (SRP).
     *
     * @see CustomerPasswordResetNotification
     */
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'address',
        'loyalty_points', 'password', 'store_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /**
     * Dispatch a password-reset notification to this customer.
     *
     * Called by PasswordBroker after a reset token is created.  The method
     * delegates to Laravel's Notification system instead of sending mail
     * directly, respecting the Single Responsibility Principle:
     *
     *   - Model          → decides WHEN to send (conditionally for OAuth)
     *   - Notification   → decides WHICH channels to use
     *   - Mailable       → renders the email content
     *
     * OAuth-only customers (password === null) are silently skipped:
     * they have no local password to reset and should not receive reset
     * links even if an attacker knows their email address.
     *
     * @param  string  $token  Reset token (60-min expiry).
     */
    public function sendPasswordResetNotification($token): void
    {
        if (! $this->password) {
            return;
        }

        $this->notify(new CustomerPasswordResetNotification($token));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }
}
