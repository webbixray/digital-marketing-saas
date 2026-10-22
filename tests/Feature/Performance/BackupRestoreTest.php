<?php

namespace Tests\Feature\Performance;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_directory_can_be_created(): void
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $this->assertTrue(is_dir($backupDir));
    }

    public function test_backup_directory_is_writable(): void
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $testFile = $backupDir . '/.write_test';
        file_put_contents($testFile, 'test');
        $this->assertFileExists($testFile);
        unlink($testFile);
    }

    public function test_database_connection_is_configured(): void
    {
        $dbConfig = config('database.connections.sqlite');
        $this->assertNotNull($dbConfig);
        $this->assertSame('sqlite', $dbConfig['driver']);
    }

    public function test_agency_data_persists_for_backup(): void
    {
        // Verify that data can be written and read (simulating backup source)
        $agency = Agency::factory()->create([
            'name' => 'Backup Test Agency',
            'slug' => 'backup-test-agency',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => 'Backup Test Agency',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'agency_id' => $agency->id,
        ]);
    }
}
