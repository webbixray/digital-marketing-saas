<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class VersionService
{
    private const CACHE_KEY = 'app:version';

    private const CHANGELOG_KEY = 'app:changelog';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the current application version string.
     */
    public function getVersion(): string
    {
        return config('version.version', '1.0.0');
    }

    /**
     * Get version info array.
     */
    public function getVersionInfo(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $version = $this->getVersion();
            $parts = explode('.', $version);

            return [
                'full' => $version,
                'major' => (int) ($parts[0] ?? 0),
                'minor' => (int) ($parts[1] ?? 0),
                'patch' => (int) ($parts[2] ?? 0),
                'codename' => config('version.codename', ''),
                'release_date' => config('version.release_date', ''),
                'minimum_php' => config('version.minimum_php', '8.4'),
                'minimum_laravel' => config('version.minimum_laravel', '13.0'),
            ];
        });
    }

    /**
     * Get changelog entries from CHANGELOG.md.
     */
    public function getChangelog(int $limit = 10): array
    {
        return Cache::remember(self::CHANGELOG_KEY.':'.$limit, self::CACHE_TTL, function () use ($limit) {
            return $this->parseChangelog($limit);
        });
    }

    /**
     * Parse CHANGELOG.md into structured array.
     */
    private function parseChangelog(int $limit): array
    {
        $path = base_path('CHANGELOG.md');

        if (! File::exists($path)) {
            return [];
        }

        $content = File::get($path);
        $entries = [];
        $currentEntry = null;

        // Parse ## [X.Y.Z] - YYYY-MM-DD sections
        preg_match_all('/## \[?([\d.]+)\]? - (\d{4}-\d{2}-\d{2})\s*
(.*?)(?=
## |\z)/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            if ($i >= $limit) {
                break;
            }

            $version = $match[1];
            $date = $match[2];
            $body = trim($match[3]);

            $entry = [
                'version' => $version,
                'date' => $date,
                'added' => [],
                'changed' => [],
                'fixed' => [],
                'security' => [],
                'deprecated' => [],
                'removed' => [],
                'raw' => $body,
            ];

            // Parse categories
            $currentCategory = null;
            $lines = explode('
', $body);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Check for category headers
                foreach (['Added', 'Changed', 'Fixed', 'Security', 'Deprecated', 'Removed'] as $cat) {
                    if (str_starts_with($line, "### {$cat}")) {
                        $currentCategory = strtolower($cat);
                        break;
                    }
                }

                // Parse bullet points
                if (preg_match('/^[-*]\s+(.+)/', $line, $bulletMatch) && $currentCategory) {
                    $entry[$currentCategory][] = $bulletMatch[1];
                }
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Get the latest release info.
     */
    public function getLatestRelease(): ?array
    {
        $changelog = $this->getChangelog(1);

        return $changelog[0] ?? null;
    }

    /**
     * Compare two version strings.
     * Returns -1 if $a < $b, 0 if equal, 1 if $a > $b.
     */
    public function compareVersions(string $a, string $b): int
    {
        return version_compare($a, $b);
    }

    /**
     * Check if an update is available.
     */
    public function isUpdateAvailable(string $latestRemoteVersion): bool
    {
        return version_compare($latestRemoteVersion, $this->getVersion(), '>');
    }

    /**
     * Get all release tags from git.
     */
    public function getGitTags(): array
    {
        $tags = [];
        exec('git tag --sort=-v:refname 2>/dev/null', $output, $returnCode);

        if ($returnCode === 0) {
            foreach ($output as $tag) {
                $tags[] = ltrim($tag, 'v');
            }
        }

        return $tags;
    }

    /**
     * Get commit messages between two versions.
     */
    public function getCommitsBetween(string $from, string $to = 'HEAD'): array
    {
        $commits = [];
        exec("git log {$from}..{$to} --oneline --no-merges 2>/dev/null", $output, $returnCode);

        if ($returnCode === 0) {
            foreach ($output as $line) {
                $commits[] = $line;
            }
        }

        return $commits;
    }

    /**
     * Generate changelog entry from conventional commits.
     */
    public function generateFromCommits(string $since = ''): array
    {
        $since = $since ?: $this->getGitTags()[0] ?? 'HEAD~10';
        $commits = $this->getCommitsBetween($since);

        $entry = [
            'added' => [],
            'changed' => [],
            'fixed' => [],
            'security' => [],
            'deprecated' => [],
            'removed' => [],
        ];

        foreach ($commits as $commit) {
            if (str_contains($commit, 'feat:') || str_contains($commit, 'feat(')) {
                $entry['added'][] = $commit;
            } elseif (str_contains($commit, 'fix:')) {
                $entry['fixed'][] = $commit;
            } elseif (str_contains($commit, 'security:') || str_contains($commit, 'sec:')) {
                $entry['security'][] = $commit;
            } elseif (str_contains($commit, 'deprecate:')) {
                $entry['deprecated'][] = $commit;
            } elseif (str_contains($commit, 'remove:')) {
                $entry['removed'][] = $commit;
            } elseif (str_contains($commit, 'refactor:') || str_contains($commit, 'perf:')) {
                $entry['changed'][] = $commit;
            }
        }

        return $entry;
    }

    /**
     * Clear version cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CHANGELOG_KEY);
    }
}
