<?php

namespace App\Services\Social;

use App\Services\Social\Contracts\SocialPlatformContract;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Single registry and entry point for every social platform driver.
 *
 * Adding a platform is now: create the driver, register it in
 * config('platform.social_drivers'), add it to SocialAccount::SUPPORTED_PLATFORMS.
 * No match() blocks anywhere else in the codebase need to change.
 */
class SocialPlatformManager
{
    /** @var array<string, SocialPlatformContract> */
    private array $resolved = [];

    public function __construct(private readonly Container $container) {}

    /**
     * Resolve the driver for a platform.
     */
    public function for(string $platform): SocialPlatformContract
    {
        $platform = strtolower($platform);

        if (isset($this->resolved[$platform])) {
            return $this->resolved[$platform];
        }

        $drivers = config('platform.social_drivers', []);

        if (! isset($drivers[$platform])) {
            throw new InvalidArgumentException("No social platform driver registered for '{$platform}'.");
        }

        $driver = $this->container->make($drivers[$platform]);

        if (! $driver instanceof SocialPlatformContract) {
            throw new InvalidArgumentException(
                "Driver for '{$platform}' must implement ".SocialPlatformContract::class.'.'
            );
        }

        return $this->resolved[$platform] = $driver;
    }

    /**
     * Whether a driver is registered for the platform.
     */
    public function has(string $platform): bool
    {
        return isset(config('platform.social_drivers', [])[strtolower($platform)]);
    }

    /**
     * All registered drivers, keyed by platform name.
     *
     * @return array<string, SocialPlatformContract>
     */
    public function all(): array
    {
        $drivers = [];

        foreach (array_keys(config('platform.social_drivers', [])) as $platform) {
            $name = (string) $platform;
            $drivers[$name] = $this->for($name);
        }

        return $drivers;
    }

    /**
     * Registered platforms that have their app credentials configured.
     *
     * @return array<int, string>
     */
    public function configured(): array
    {
        $configured = [];

        foreach (array_keys(config('platform.social_drivers', [])) as $platform) {
            $name = (string) $platform;
            if ($this->for($name)->isConfigured()) {
                $configured[] = $name;
            }
        }

        return $configured;
    }
}
