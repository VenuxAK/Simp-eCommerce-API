<?php

namespace App\Modules\Supplier\Models;

use App\Modules\Catalog\Models\Product;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'notes'];

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
