<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Notifications\StaffPasswordResetNotification;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Core\Enums\UserRole;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Sales\Models\Order;
use App\Modules\Store\Models\Store;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Internal staff/auth user — separate from e-commerce customer accounts.
 *
 * Supports three roles (Root, StoreAdmin, Staff) and is tied to a store
 * for multi-tenant scoping. This is NOT the same as Customer, which uses
 * a independent auth guard.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static string $factory = UserFactory::class;

    protected $fillable = ['name', 'email', 'password', 'role', 'store_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Dispatch a password-reset notification to this staff user.
     *
     * Called by PasswordBroker after a reset token is created.  Delegates
     * to Laravel's Notification system instead of sending mail directly —
     * the model decides WHEN to notify, the Notification decides WHICH
     * channels, and the Mailable renders the content (SRP).
     *
     * @param  string  $token  Reset token (60-min expiry).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StaffPasswordResetNotification($token));
    }

    public function isRoot(): bool
    {
        return $this->role === UserRole::Root;
    }

    public function isStoreAdmin(): bool
    {
        return $this->role === UserRole::StoreAdmin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // TODO: Replace with contract calls when Sales/Cash/Inventory/Audit modules are extracted.
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
