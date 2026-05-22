<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class BackupTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::disk('local')->deleteDirectory('backups');
        Storage::disk('local')->makeDirectory('backups');
        File::copy(database_path('database.sqlite'), Storage::disk('local')->path('backups/test-backup.sqlite'));
    }

    public function test_can_list_backups(): void
    {
        $response = $this->getJson('/api/backups', $this->adminHeaders);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_download_backup(): void
    {
        $response = $this->getJson('/api/backups/test-backup.sqlite/download', $this->adminHeaders);
        $response->assertOk();
    }

    public function test_backup_download_rejects_path_traversal(): void
    {
        $response = $this->getJson('/api/backups/..%2F..%2F..%2F.env/download', $this->adminHeaders);
        $response->assertStatus(404);
    }

    public function test_backup_download_rejects_direct_path(): void
    {
        $response = $this->getJson('/api/backups/../../../.env/download', $this->adminHeaders);
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

        $response = $this->postJson('/api/backups', [], $staffHeaders);
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
