<?php

namespace App\Modules\Identity\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // TODO: Replace with contract calls when Sales/Cash/Inventory/Audit modules are extracted.
    public function orders(): HasMany
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(\App\Models\CashSession::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(\App\Models\AuditLog::class);
    }
}
