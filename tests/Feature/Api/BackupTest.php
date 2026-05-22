<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->headers = ['Authorization' => "Bearer {$admin->createToken('test')->plainTextToken}"];

        Storage::disk('local')->deleteDirectory('backups');
        Storage::disk('local')->makeDirectory('backups');
        File::copy(database_path('database.sqlite'), Storage::disk('local')->path('backups/test-backup.sqlite'));
    }

    public function test_can_list_backups(): void
    {
        $response = $this->getJson('/api/backups', $this->headers);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_download_backup(): void
    {
        $response = $this->getJson('/api/backups/test-backup.sqlite/download', $this->headers);
        $response->assertOk();
    }

    public function test_backup_download_rejects_path_traversal(): void
    {
        $response = $this->getJson('/api/backups/..%2F..%2F..%2F.env/download', $this->headers);
        $response->assertStatus(404);
    }

    public function test_backup_download_rejects_direct_path(): void
    {
        $response = $this->getJson('/api/backups/../../../.env/download', $this->headers);
        $response->assertStatus(404);
    }

    public function test_staff_cannot_list_backups(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];

        $response = $this->getJson('/api/backups', $staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_create_backup(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];

        $response = $this->postJson('/api/backup', [], $staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_download_backup(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];

        $response = $this->getJson('/api/backups/test-backup.sqlite/download', $staffHeaders);
        $response->assertForbidden();
    }
}
