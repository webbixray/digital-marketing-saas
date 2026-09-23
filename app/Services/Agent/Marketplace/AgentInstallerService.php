<?php

namespace App\Services\Agent\Marketplace;

use App\Models\Agency;
use App\Models\AgentMarketplaceItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgentInstallerService
{
    public function install(AgentMarketplaceItem $item, int $agencyId, array $config = []): array
    {
        try {
            $status = $this->getInstallationStatus($item->id, $agencyId);

            if ($status['installed']) {
                return [
                    'success' => false,
                    'message' => 'This agent is already installed.',
                ];
            }

            $validation = $this->validateRequirements($item, $agencyId);

            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Requirements not met: ' . implode(', ', $validation['missing']),
                ];
            }

            // Persist installation record
            $this->recordInstallation($item, $agencyId, $config);

            Log::info('Agent installed', [
                'item_id' => $item->id,
                'agency_id' => $agencyId,
                'agent_name' => $item->name,
            ]);

            return [
                'success' => true,
                'message' => "{$item->name} installed successfully.",
                'config' => $config,
            ];
        } catch (\Exception $e) {
            Log::error('Agent installation failed', [
                'item_id' => $item->id,
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ];
        }
    }

    public function uninstall(AgentMarketplaceItem $item, int $agencyId): array
    {
        try {
            $installPath = "agent_installations/{$agency_id}/{$item->slug}";

            // Remove stored configuration
            if (Storage::exists($installPath)) {
                Storage::deleteDirectory($installPath);
            }

            Log::info('Agent uninstalled', [
                'item_id' => $item->id,
                'agency_id' => $agencyId,
                'agent_name' => $item->name,
            ]);

            return [
                'success' => true,
                'message' => "{$item->name} has been uninstalled.",
            ];
        } catch (\Exception $e) {
            Log::error('Agent uninstallation failed', [
                'item_id' => $item->id,
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Uninstallation failed: ' . $e->getMessage(),
            ];
        }
    }

    public function configure(AgentMarketplaceItem $item, array $config): array
    {
        try {
            $agencyId = auth()->user()->agency_id;
            $installPath = "agent_installations/{$agencyId}/{$item->slug}";

            if (! Storage::exists($installPath)) {
                Storage::makeDirectory($installPath);
            }

            Storage::put("{$installPath}/config.json", json_encode($config, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'message' => 'Configuration saved successfully.',
                'config' => $config,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to save configuration: ' . $e->getMessage(),
            ];
        }
    }

    public function validateRequirements(AgentMarketplaceItem $item, int $agencyId): array
    {
        $agency = Agency::findOrFail($agencyId);
        $missing = [];
        $requirements = $item->requirements ?? [];

        foreach ($requirements as $req) {
            if (str_contains($req, 'feature enabled')) {
                $feature = strtolower(str_replace(' feature enabled', '', $req));
                // In production, check agency plan features
                // For now, we trust the free/paid gating at the marketplace level
            }
        }

        // Check if agency plan allows installation based on pricing_type
        if ($item->pricing_type === 'paid' || $item->pricing_type === 'pricing_tiers') {
            if ($agency->subscription_plan === 'free') {
                $missing[] = 'Paid plan required for this agent';
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }

    public function getInstallationStatus(int $itemId, int $agencyId): array
    {
        $item = AgentMarketplaceItem::findOrFail($itemId);
        $installPath = "agent_installations/{$agencyId}/{$item->slug}";
        $installed = Storage::exists($installPath);
        $config = null;

        if ($installed) {
            $configPath = "{$installPath}/config.json";
            if (Storage::exists($configPath)) {
                $config = json_decode(Storage::get($configPath), true);
            }
        }

        return [
            'installed' => $installed,
            'configured' => $config !== null,
            'config' => $config,
        ];
    }

    private function recordInstallation(AgentMarketplaceItem $item, int $agencyId, array $config): void
    {
        $installPath = "agent_installations/{$agencyId}/{$item->slug}";

        if (! Storage::exists($installPath)) {
            Storage::makeDirectory($installPath);
        }

        $record = [
            'item_id' => $item->id,
            'installed_at' => now()->toIso8601String(),
            'config' => $config,
        ];

        Storage::put("{$installPath}/install.json", json_encode($record, JSON_PRETTY_PRINT));

        if (! empty($config)) {
            Storage::put("{$installPath}/config.json", json_encode($config, JSON_PRETTY_PRINT));
        }
    }
}
