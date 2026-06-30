<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use Tests\ApiTestCase;

class CategoryTest extends ApiTestCase
{
    public function test_can_list_categories(): void
    {
        Category::factory(3)->create();

        $response = $this->getJson('/api/v1/categories', $this->adminHeaders);

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'T-Shirts',
            'description' => 'All kinds of t-shirts',
        ], $this->adminHeaders);

        $response->assertCreated()->assertJsonPath('data.name', 'T-Shirts');
        $this->assertDatabaseHas('categories', ['name' => 'T-Shirts']);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'T-Shirts']);

        $response = $this->postJson('/api/v1/categories', ['name' => 'T-Shirts'], $this->adminHeaders);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}", $this->adminHeaders);

        $response->assertOk()->assertJsonPath('data.id', $category->id);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated Name',
        ], $this->adminHeaders);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}", [], $this->adminHeaders);

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_staff_cannot_create_category(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Staff Category',
        ], $this->staffHeaders);

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Hacked Name',
        ], $this->staffHeaders);

        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}", [], $this->staffHeaders);

        $response->assertForbidden();
    }
}
