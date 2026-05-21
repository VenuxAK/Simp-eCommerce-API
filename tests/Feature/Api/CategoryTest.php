<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_list_categories(): void
    {
        Category::factory(3)->create();

        $response = $this->getJson('/api/categories', $this->headers);

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'T-Shirts',
            'description' => 'All kinds of t-shirts',
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.name', 'T-Shirts');
        $this->assertDatabaseHas('categories', ['name' => 'T-Shirts']);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'T-Shirts']);

        $response = $this->postJson('/api/categories', ['name' => 'T-Shirts'], $this->headers);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}", $this->headers);

        $response->assertOk()->assertJsonPath('data.id', $category->id);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ], $this->headers);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}", [], $this->headers);

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
