<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Generates deterministic initial-letter avatars as inline SVG.
 * Replaces the external ui-avatars.com dependency (privacy: no user names
 * sent to a third party; reliability: no external network call).
 */
class AvatarController extends Controller
{
    private const PALETTES = [
        ['#6366f1', '#ffffff'],
        ['#10b981', '#ffffff'],
        ['#f59e0b', '#ffffff'],
        ['#ef4444', '#ffffff'],
        ['#3b82f6', '#ffffff'],
        ['#8b5cf6', '#ffffff'],
        ['#ec4899', '#ffffff'],
        ['#14b8a6', '#ffffff'],
    ];

    public function show(string $name, string $background = '6366f1', string $color = 'fff', int $size = 64): Response
    {
        $name = trim(urldecode($name));
        $initials = $this->initials($name);
        [$bg, $fg] = $background === 'random'
            ? $this->paletteFor($name)
            : ['#'.$this->normalizeHex($background), '#'.$this->normalizeHex($color)];

        $size = min(256, max(16, $size));
        $fontSize = round($size * 0.4);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}" role="img" aria-label="{$initials}">
  <rect width="100%" height="100%" fill="{$bg}"/>
  <text x="50%" y="50%" dy="0.35em" text-anchor="middle" dominant-baseline="middle"
        font-family="Inter, system-ui, -apple-system, sans-serif" font-size="{$fontSize}"
        font-weight="600" fill="{$fg}">{$initials}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Derive up to two initials from a display name.
     */
    private function initials(string $name): string
    {
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0], 0, 1);

        if (count($parts) >= 2) {
            return mb_strtoupper($first.mb_substr(end($parts), 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    /**
     * Deterministic palette selection for a given name (stable across requests).
     *
     * @return array{0: string, 1: string}
     */
    private function paletteFor(string $name): array
    {
        return self::PALETTES[crc32($name) % count(self::PALETTES)];
    }

    private function normalizeHex(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? $hex : '6366f1';
    }
}
