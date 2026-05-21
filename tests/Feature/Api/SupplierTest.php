<?php

namespace Tests\Feature\Api;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_create_supplier(): void
    {
        $response = $this->postJson('/api/suppliers', [
            'name' => 'Fashion Wholesale', 'contact_person' => 'Mr. Lee',
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.name', 'Fashion Wholesale');
    }

    public function test_can_list_suppliers(): void
    {
        Supplier::factory(3)->create();
        $this->getJson('/api/suppliers', $this->headers)->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $this->putJson("/api/suppliers/{$supplier->id}", ['name' => 'Updated'], $this->headers)
            ->assertOk()->assertJsonPath('data.name', 'Updated');
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $this->deleteJson("/api/suppliers/{$supplier->id}", [], $this->headers)->assertOk();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
