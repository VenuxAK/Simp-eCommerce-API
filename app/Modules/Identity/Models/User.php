<?php

namespace App\Modules\Identity\Models;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Cash\Models\CashSession;
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
        ];
    }

    public function isRoot(): bool
    {
        return $this->role === 'root';
    }

    public function isStoreAdmin(): bool
    {
        return $this->role === 'store_admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
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
