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
use Spatie\Permission\Traits\HasRoles;

/**
 * Internal staff/auth user — separate from e-commerce customer accounts.
 *
 * Supports five roles (Root, StoreOwner, StoreManager, InventoryStaff, SalesStaff)
 * and is tied to a store for multi-tenant scoping. This is NOT the same as
 * Customer, which uses a independent auth guard.
 *
 * @see UserRole
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected static string $factory = UserFactory::class;

    protected $fillable = ['name', 'email', 'password', 'store_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $with = ['roles'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the primary role name (from the first Spatie role assigned).
     * Replaces the old `users.role` column.
     */
    public function getRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
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
        return $this->role === UserRole::Root->value;
    }

    public function isStoreOwner(): bool
    {
        return $this->role === UserRole::StoreOwner->value;
    }

    public function isStoreManager(): bool
    {
        return $this->role === UserRole::StoreManager->value;
    }

    public function isInventoryStaff(): bool
    {
        return $this->role === UserRole::InventoryStaff->value;
    }

    public function isSalesStaff(): bool
    {
        return $this->role === UserRole::SalesStaff->value;
    }

    /**
     * Any store-level role that has operational access (not Root).
     */
    public function isStoreUser(): bool
    {
        return in_array($this->role, [
            UserRole::StoreOwner,
            UserRole::StoreManager,
            UserRole::InventoryStaff,
            UserRole::SalesStaff,
        ], true);
    }

    /**
     * Roles that can manage other users within their store.
     */
    public function canManageStoreUsers(): bool
    {
        return in_array($this->role, [
            UserRole::Root,
            UserRole::StoreOwner,
        ], true);
    }

    /**
     * Has permission to manage catalog (products, categories, brands).
     */
    public function canManageCatalog(): bool
    {
        return $this->isRoot() || $this->hasAnyRole(['store_owner', 'store_manager', 'inventory_staff']);
    }

    /**
     * Has permission to manage sales (orders, discounts, returns).
     */
    public function canManageSales(): bool
    {
        return $this->isRoot() || $this->hasAnyRole(['store_owner', 'store_manager']);
    }

    /**
     * Has permission to manage suppliers.
     */
    public function canManageSuppliers(): bool
    {
        return $this->canManageCatalog();
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
