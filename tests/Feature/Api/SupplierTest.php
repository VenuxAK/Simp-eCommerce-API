<?php

namespace Tests\Feature\Api;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private array $adminHeaders;
    private array $staffHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $this->adminHeaders = ['Authorization' => "Bearer {$admin->createToken('test')->plainTextToken}"];
        $this->staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];
    }

    public function test_can_create_supplier(): void
    {
        $response = $this->postJson('/api/suppliers', [
            'name' => 'Fashion Wholesale', 'contact_person' => 'Mr. Lee',
        ], $this->adminHeaders);

        $response->assertCreated()->assertJsonPath('data.name', 'Fashion Wholesale');
    }

    public function test_can_list_suppliers(): void
    {
        Supplier::factory(3)->create();
        $this->getJson('/api/suppliers', $this->adminHeaders)->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $this->putJson("/api/suppliers/{$supplier->id}", ['name' => 'Updated'], $this->adminHeaders)
            ->assertOk()->assertJsonPath('data.name', 'Updated');
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $this->deleteJson("/api/suppliers/{$supplier->id}", [], $this->adminHeaders)->assertOk();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_staff_cannot_create_supplier(): void
    {
        $response = $this->postJson('/api/suppliers', [
            'name' => 'Staff Supplier',
        ], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $response = $this->putJson("/api/suppliers/{$supplier->id}", ['name' => 'Hacked'], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $response = $this->deleteJson("/api/suppliers/{$supplier->id}", [], $this->staffHeaders);
        $response->assertForbidden();
    }
}
