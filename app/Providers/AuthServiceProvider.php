<?php

namespace App\Providers;

use App\Modules\Audit\Policies\AuditLogPolicy;
use App\Modules\Cash\Policies\CashSessionPolicy;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Policies\BrandPolicy;
use App\Modules\Catalog\Policies\CategoryPolicy;
use App\Modules\Catalog\Policies\ProductPolicy;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Policies\StockMovementPolicy;
use App\Modules\Promotion\Models\Discount;
use App\Modules\Promotion\Policies\DiscountPolicy;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Policies\InvoicePolicy;
use App\Modules\Sales\Policies\OrderPolicy;
use App\Modules\Store\Models\Store;
use App\Modules\Store\Policies\StorePolicy;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Policies\SupplierPolicy;
use App\Modules\System\Policies\BackupPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Brand::class => BrandPolicy::class,
        Order::class => OrderPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Customer::class => CustomerPolicy::class,
        Discount::class => DiscountPolicy::class,
        Supplier::class => SupplierPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        Store::class => StorePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
