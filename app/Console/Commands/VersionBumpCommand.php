<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VersionBumpCommand extends Command
{
    protected $signature = 'version:bump 
        {type : The bump type: major, minor, patch}
        {--prerelease= : Prerelease tag (e.g., alpha, beta, rc)}
        {--changelog= : Path to CHANGELOG.md}';

    protected $description = 'Bump the application version (semver)';

    public function handle(VersionService $version): int
    {
        $type = $this->argument('type');
        $prerelease = $this->option('prerelease');

        if (! in_array($type, ['major', 'minor', 'patch'])) {
            $this->error('Invalid bump type. Use: major, minor, patch');

            return self::FAILURE;
        }

        $current = $version->getVersion();
        $parts = explode('.', $current);
        // Ensure we have at least 3 parts
        $parts = array_pad($parts, 3, '0');

        $major = (int) $parts[0];
        $minor = (int) $parts[1];
        $patch = (int) $parts[2];

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
                $patch++;
                break;
        }

        $newVersion = "{$major}.{$minor}.{$patch}";

        if ($prerelease) {
            $newVersion .= "-{$prerelease}";
        }

        // Update config/version.php
        $this->updateVersionConfig($newVersion);

        $this->info("Version bumped from {$current} to {$newVersion}");
        $this->info("Run 'php artisan version:changelog' to generate changelog entries.");

        return self::SUCCESS;
    }

    private function updateVersionConfig(string $version): void
    {
        $path = config_path('version.php');
        $content = File::get($path);
        $content = preg_replace(
            "/'version' => '[^']+'/",
            "'version' => '{$version}'",
            $content
        );
        File::put($path, (string) $content);
    }
}
