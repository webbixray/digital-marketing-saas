<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;

class SystemBackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display backup management page.
     */
    public function index()
    {
        $backups = $this->backupService->listBackups();

        return view('system.backup', compact('backups'));
    }

    /**
     * Create a new backup.
     */
    public function store(Request $request)
    {
        $result = $this->backupService->createBackup();

        if ($result['success']) {
            return back()->with('success', "Backup created: {$result['filename']}");
        }

        return back()->with('error', "Backup failed: {$result['error']}");
    }

    /**
     * Restore from a backup.
     */
    public function restore(Request $request, string $filename)
    {
        $result = $this->backupService->restoreBackup($filename);

        if ($result['success']) {
            return back()->with('success', 'Backup restored successfully');
        }

        return back()->with('error', "Restore failed: {$result['error']}");
    }
}
