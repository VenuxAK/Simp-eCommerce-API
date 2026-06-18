<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Enums\InvoiceStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Invoice;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    private ?int $orderId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $store = \App\Modules\Store\Models\Store::firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Test Store', 'is_active' => true],
        );

        $user = User::factory()->root()->create(['store_id' => $store->id]);
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}", 'X-Store' => 'main'];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'price_adjustment' => 0,
        ]);

        $orderRes = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 50],
        ], $this->headers);

        $orderRes->assertCreated();
        $this->orderId = $orderRes->json('data.id');
    }

    public function test_can_list_invoices(): void
    {
        $response = $this->getJson('/api/invoices', $this->headers);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_invoices_by_status(): void
    {
        $response = $this->getJson('/api/invoices?status=issued', $this->headers);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_show_invoice(): void
    {
        $invoice = Invoice::first();
        $response = $this->getJson("/api/invoices/{$invoice->id}", $this->headers);
        $response->assertOk()->assertJsonPath('data.id', $invoice->id);
    }

    public function test_can_print_invoice(): void
    {
        $invoice = Invoice::first();
        $response = $this->getJson("/api/invoices/{$invoice->id}/print", $this->headers);
        $response->assertOk()->assertJsonStructure(['invoice', 'shop_name', 'shop_address', 'shop_phone']);
    }

    public function test_invoice_status_syncs_with_order(): void
    {
        $invoice = Invoice::first();
        $this->patchJson("/api/orders/{$this->orderId}/status", ['status' => 'cancelled'], $this->headers);
        $this->assertEquals(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }
}
