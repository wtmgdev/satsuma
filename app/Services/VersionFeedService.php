<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class VersionFeedService
{
    /**
     * PHP security-support end dates per branch, from php.net.
     */
    private const PHP_EOL_DATES = [
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
        '8.6' => '2030-12-31',
    ];

    /**
     * Compare a site's WP/PHP versions against the latest releases.
     */
    public function check(Site $site): array
    {
        $php = [
            'installed' => $site->php_version,
            'latest_patch' => null,
            'eol' => false,
            'eol_date' => null,
        ];

        if ($site->php_version !== null) {
            $branch = implode('.', array_slice(explode('.', $site->php_version), 0, 2));

            $php['latest_patch'] = $this->latestPhpPatch($branch);
            $php['eol_date'] = self::PHP_EOL_DATES[$branch] ?? null;
            $php['eol'] = $php['eol_date'] !== null && now()->isAfter($php['eol_date']);
        }

        $latestWp = $this->latestWpVersion();

        return [
            'php' => $php,
            'wp' => [
                'installed' => $site->wp_version,
                'latest' => $latestWp,
                'update_available' => $site->wp_version !== null
                    && $latestWp !== null
                    && version_compare($site->wp_version, $latestWp, '<'),
            ],
        ];
    }

    public function latestPhpPatch(string $branch): ?string
    {
        return Cache::remember(
            "version-feed:php:{$branch}",
            now()->addDay(),
            function () use ($branch) {
                try {
                    $response = Http::timeout(5)
                        ->get('https://www.php.net/releases/index.php', [
                            'json' => true,
                            'version' => $branch,
                        ]);

                    $version = $response->json('version');

                    return is_string($version) ? $version : null;
                } catch (Throwable) {
                    return null;
                }
            },
        );
    }

    public function latestWpVersion(): ?string
    {
        return Cache::remember('version-feed:wp:latest', now()->addHours(12), function () {
            try {
                $response = Http::timeout(5)
                    ->get('https://api.wordpress.org/core/version-check/1.7/');

                $version = $response->json('offers.0.version');

                return is_string($version) ? $version : null;
            } catch (Throwable) {
                return null;
            }
        });
    }
}
