<?php

namespace App\Modules\System\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupService
{
    public function create(): array
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

        return ['message' => 'Backup created.', 'filename' => $filename];
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
        $conn = DB::connection();
        $process = new Process([
            'pg_dump',
            '--host='.$conn->getConfig('host'),
            '--port='.$conn->getConfig('port'),
            '--username='.$conn->getConfig('username'),
            '--dbname='.$conn->getConfig('database'),
            '--file='.$backupPath,
        ]);

        $process->setEnv(['PGPASSWORD' => $conn->getConfig('password')]);
        $process->mustRun();
    }

    private function dumpMysql(string $backupPath): void
    {
        $conn = DB::connection();
        $process = new Process([
            'mysqldump',
            '--host='.$conn->getConfig('host'),
            '--port='.$conn->getConfig('port'),
            '--user='.$conn->getConfig('username'),
            $conn->getConfig('database'),
        ]);

        $process->setEnv(['MYSQL_PWD' => $conn->getConfig('password')]);
        $process->mustRun();

        file_put_contents($backupPath, $process->getOutput());
    }

    public function list(): array
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

        return $files->toArray();
    }
}
