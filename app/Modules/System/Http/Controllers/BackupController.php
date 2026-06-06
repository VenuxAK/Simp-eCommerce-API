<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    use ApiResponse;

    public function create(): JsonResponse
    {
        Storage::disk('local')->makeDirectory('backups');

        $driver = DB::getDriverName();
        $filename = 'backup-'.now()->format('Y-m-d-His').'.'.$driver;
        $backupPath = Storage::disk('local')->path("backups/{$filename}");

        match ($driver) {
            'sqlite' => $this->dumpSqlite($backupPath),
            'pgsql' => $this->dumpPgsql($backupPath),
            'mysql' => $this->dumpMysql($backupPath),
            default => abort(500, "Backup not supported for driver: {$driver}"),
        };

        return $this->respond(['message' => 'Backup created.', 'filename' => $filename]);
    }

    private function dumpSqlite(string $backupPath): void
    {
        $dbPath = config('database.connections.sqlite.database');

        if ($dbPath === ':memory:') {
            abort(500, 'Cannot backup an in-memory SQLite database.');
        }

        if (! file_exists($dbPath)) {
            abort(404, 'Database file not found.');
        }

        copy($dbPath, $backupPath);
    }

    private function dumpPgsql(string $backupPath): void
    {
        $process = new Process([
            'pg_dump',
            '--host='.config('database.connections.pgsql.host'),
            '--port='.config('database.connections.pgsql.port'),
            '--username='.config('database.connections.pgsql.username'),
            '--dbname='.config('database.connections.pgsql.database'),
            '--file='.$backupPath,
        ]);

        $process->setEnv(['PGPASSWORD' => config('database.connections.pgsql.password')]);
        $process->mustRun();
    }

    private function dumpMysql(string $backupPath): void
    {
        $process = new Process([
            'mysqldump',
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            '--user='.config('database.connections.mysql.username'),
            '--password='.config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
        ], timeout: null);

        file_put_contents($backupPath, $process->mustRun()->getOutput());
    }

    public function list(): JsonResponse
    {
        Storage::disk('local')->makeDirectory('backups');
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($f) => str_starts_with(basename($f), 'backup-'))
            ->map(fn ($f) => [
                'filename' => basename($f),
                'size' => Storage::disk('local')->size($f),
                'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return $this->respond(['data' => $files]);
    }

    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $filename = basename($filename);

        $path = "backups/{$filename}";
        if (! Storage::disk('local')->exists($path)) {
            return $this->respondError('Backup not found.', 404);
        }

        return response()->download(Storage::disk('local')->path($path));
    }
}
