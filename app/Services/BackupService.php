<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    private string $backupPath = 'backups';

    private int $maxBackups = 7;

    /**
     * Create a full system backup.
     */
    public function createBackup(): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_{$timestamp}.zip";
        $localPath = storage_path("app/{$this->backupPath}/{$filename}");

        try {
            // Ensure backup directory exists
            if (! is_dir(dirname($localPath))) {
                mkdir(dirname($localPath), 0755, true);
            }

            // Create ZIP archive
            $zip = new \ZipArchive;
            if ($zip->open($localPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Cannot create backup archive');
            }

            // Add database dump
            $this->addDatabaseDump($zip);

            // Add storage files
            $this->addStorageFiles($zip);

            $zip->close();

            // Upload to cloud storage if configured
            $this->uploadToCloud($localPath, $filename);

            // Clean up old backups
            $this->cleanupOldBackups();

            Log::info("Backup created successfully: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($localPath)),
                'path' => $localPath,
            ];
        } catch (Exception $e) {
            Log::error("Backup failed: {$e->getMessage()}");

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add database dump to ZIP.
     */
    private function addDatabaseDump(\ZipArchive $zip): void
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $dbPath = database_path('database.sqlite');
            if (file_exists($dbPath)) {
                $zip->addFile($dbPath, 'database/database.sqlite');
            }
        } else {
            // For MySQL/PostgreSQL, use command-line dump
            $filename = 'database/dump.sql';
            $tempFile = tempnam(sys_get_temp_dir(), 'db_dump');

            if ($driver === 'mysql') {
                $command = sprintf(
                    'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                    escapeshellarg(config('database.connections.mysql.host')),
                    escapeshellarg(config('database.connections.mysql.port')),
                    escapeshellarg(config('database.connections.mysql.username')),
                    escapeshellarg(config('database.connections.mysql.password')),
                    escapeshellarg(config('database.connections.mysql.database')),
                    escapeshellarg($tempFile)
                );
            } else {
                $command = sprintf(
                    'PGPASSWORD=%s pg_dump --host=%s --port=%s --user=%s %s > %s',
                    escapeshellarg(config('database.connections.pgsql.password')),
                    escapeshellarg(config('database.connections.pgsql.host')),
                    escapeshellarg(config('database.connections.pgsql.port')),
                    escapeshellarg(config('database.connections.pgsql.username')),
                    escapeshellarg(config('database.connections.pgsql.database')),
                    escapeshellarg($tempFile)
                );
            }

            exec($command);
            $zip->addFile($tempFile, $filename);
            unlink($tempFile);
        }
    }

    /**
     * Add storage files to ZIP.
     */
    private function addStorageFiles(\ZipArchive $zip): void
    {
        $storagePath = storage_path('app/public');
        if (! is_dir($storagePath)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getRealPath();
            $relativePath = 'storage/'.substr($filePath, strlen($storagePath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    /**
     * Upload backup to cloud storage.
     */
    private function uploadToCloud(string $localPath, string $filename): void
    {
        try {
            $disk = config('backup.disk', 'local');
            if ($disk !== 'local') {
                Storage::disk($disk)->put(
                    "{$this->backupPath}/{$filename}",
                    file_get_contents($localPath)
                );
            }
        } catch (Exception $e) {
            Log::warning("Failed to upload backup to cloud: {$e->getMessage()}");
        }
    }

    /**
     * Clean up old backups.
     */
    private function cleanupOldBackups(): void
    {
        $files = Storage::disk('local')->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            $backups[$file] = Storage::disk('local')->lastModified($file);
        }

        arsort($backups);
        $toDelete = array_slice(array_keys($backups), $this->maxBackups);

        foreach ($toDelete as $file) {
            Storage::disk('local')->delete($file);
            Log::info("Deleted old backup: {$file}");
        }
    }

    /**
     * Format bytes to human-readable.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get list of available backups.
     */
    public function listBackups(): array
    {
        $files = Storage::disk('local')->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatBytes(Storage::disk('local')->size($file)),
                'date' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
            ];
        }

        usort($backups, fn ($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

        return $backups;
    }

    /**
     * Restore from a backup file.
     */
    public function restoreBackup(string $filename): array
    {
        $path = storage_path("app/{$this->backupPath}/{$filename}");

        if (! file_exists($path)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        try {
            $zip = new \ZipArchive;
            if ($zip->open($path) !== true) {
                throw new Exception('Cannot open backup archive');
            }

            $zip->extractTo(storage_path('app/restore_temp'));
            $zip->close();

            // Restore database
            $this->restoreDatabase();

            // Restore storage files
            $this->restoreStorage();

            // Clean up
            $this->removeDirectory(storage_path('app/restore_temp'));

            Log::info("Backup restored successfully: {$filename}");

            return ['success' => true];
        } catch (Exception $e) {
            Log::error("Restore failed: {$e->getMessage()}");

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restore database from backup.
     */
    private function restoreDatabase(): void
    {
        $driver = config('database.default');
        $dumpFile = storage_path('app/restore_temp/database/dump.sql');

        if ($driver === 'sqlite') {
            $sqliteFile = storage_path('app/restore_temp/database/database.sqlite');
            if (file_exists($sqliteFile)) {
                copy($sqliteFile, database_path('database.sqlite'));
            }
        } elseif (file_exists($dumpFile)) {
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg(config('database.connections.mysql.host')),
                escapeshellarg(config('database.connections.mysql.port')),
                escapeshellarg(config('database.connections.mysql.username')),
                escapeshellarg(config('database.connections.mysql.password')),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($dumpFile)
            );
            exec($command);
        }
    }

    /**
     * Restore storage files from backup.
     */
    private function restoreStorage(): void
    {
        $storageBackup = storage_path('app/restore_temp/storage');
        if (! is_dir($storageBackup)) {
            return;
        }

        $targetPath = storage_path('app/public');
        $this->removeDirectory($targetPath);
        rename($storageBackup, $targetPath);
    }

    /**
     * Remove directory recursively.
     */
    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "{$path}/{$file}";
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($path);
    }
}
