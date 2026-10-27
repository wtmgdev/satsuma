<?php

use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Services\VersionFeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function siteWithVersions(string $php, string $wp): Site
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->members()->attach($user->id, ['role' => 'owner']);

    return Site::factory()->for($organization)->create([
        'php_version' => $php,
        'wp_version' => $wp,
    ]);
}

it('flags patch updates, EOL branches and wp core updates', function () {
    Cache::forget('version-feed:php:8.1');
    Cache::forget('version-feed:php:8.3');
    Cache::forget('version-feed:wp:latest');

    Http::fake([
        'www.php.net/releases/index.php*' => Http::sequence()
            ->push(['version' => '8.1.33'])
            ->push(['version' => '8.3.36']),
        'api.wordpress.org/core/version-check/*' => Http::response([
            'offers' => [['version' => '7.1.3', 'response' => 'upgrade']],
        ]),
    ]);

    $eolSite = siteWithVersions('8.1.30', '6.8.1');
    $currentSite = siteWithVersions('8.3.36', '7.1.3');

    $eolCheck = (new VersionFeedService)->check($eolSite);
    $currentCheck = (new VersionFeedService)->check($currentSite);

    expect($eolCheck['php']['eol'])->toBeTrue()
        ->and($eolCheck['php']['latest_patch'])->toBe('8.1.33')
        ->and($eolCheck['wp']['update_available'])->toBeTrue()
        ->and($eolCheck['wp']['latest'])->toBe('7.1.3')
        ->and($currentCheck['php']['eol'])->toBeFalse()
        ->and($currentCheck['php']['latest_patch'])->toBe('8.3.36')
        ->and($currentCheck['wp']['update_available'])->toBeFalse();
});

it('caches the version feeds', function () {
    Http::fake([
        'www.php.net/releases/index.php*' => Http::response(['version' => '8.3.36']),
        'api.wordpress.org/core/version-check/*' => Http::response([
            'offers' => [['version' => '7.1.3']],
        ]),
    ]);

    $site = siteWithVersions('8.3.30', '7.1.3');
    $service = new VersionFeedService;

    $service->check($site);
    $service->check($site);

    Http::assertSentCount(2);
});
