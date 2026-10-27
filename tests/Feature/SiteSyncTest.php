<?php

use App\ConnectionStatus;
use App\Actions\NotifyOrganizationMembers;
use App\Jobs\SyncSiteStatusJob;
use App\Models\Site;
use App\Models\SitePlugin;
use App\Models\SiteTheme;
use App\Services\WordPressClientService;
use Illuminate\Support\Facades\Http;

function siteInventoryPayload(): array
{
    return [
        'wp_version' => '6.8.1',
        'php_version' => '8.3.2',
        'plugins' => [
            ['name' => 'Akismet', 'slug' => 'akismet', 'version' => '5.3', 'is_active' => true, 'update_available' => true, 'new_version' => '5.4'],
            ['name' => 'Yoast SEO', 'slug' => 'wordpress-seo', 'version' => '24.0', 'is_active' => false, 'update_available' => false, 'new_version' => null],
        ],
        'themes' => [
            ['name' => 'Twenty Twenty-Five', 'slug' => 'twentytwentyfive', 'version' => '1.2', 'is_active' => true, 'update_available' => false, 'new_version' => null],
        ],
    ];
}

it('fetches site inventory with the agent token header', function () {
    $site = Site::factory()->create(['url' => 'https://example.test', 'api_token' => 'secret-token']);

    Http::fake([
        'example.test/wp-json/agent/v1/system-info' => Http::response(siteInventoryPayload()),
    ]);

    $inventory = (new WordPressClientService)->fetchSiteInventory($site);

    expect($inventory)->toBe(siteInventoryPayload());

    Http::assertSent(fn ($request) => $request->header('X-Agent-Token')[0] === 'secret-token'
        && str_starts_with($request->url(), 'https://example.test'));
});

it('marks the site offline and returns an empty array when the request fails', function () {
    $site = Site::factory()->create(['url' => 'https://example.test']);

    Http::fake(fn () => Http::response(null, 500));

    $inventory = (new WordPressClientService)->fetchSiteInventory($site);

    expect($inventory)->toBe([])
        ->and($site->refresh()->connection_status)->toBe(ConnectionStatus::Offline);
});

it('syncs site status and inventory on the job', function () {
    $site = Site::factory()->create(['url' => 'https://example.test']);
    SitePlugin::factory()->create(['site_id' => $site->id, 'slug' => 'removed-plugin']);

    Http::fake([
        'example.test/wp-json/agent/v1/system-info' => Http::response(siteInventoryPayload()),
    ]);

    (new SyncSiteStatusJob($site))->handle(new WordPressClientService, app(NotifyOrganizationMembers::class));

    $site->refresh();

    expect($site->connection_status)->toBe(ConnectionStatus::Online)
        ->and($site->wp_version)->toBe('6.8.1')
        ->and($site->php_version)->toBe('8.3.2')
        ->and($site->last_ping_at)->not->toBeNull()
        ->and($site->plugins->pluck('slug')->sort()->values()->all())->toBe(['akismet', 'wordpress-seo'])
        ->and($site->plugins->firstWhere('slug', 'akismet')->update_available)->toBeTrue()
        ->and($site->plugins->firstWhere('slug', 'akismet')->new_version)->toBe('5.4')
        ->and($site->themes->pluck('slug')->all())->toBe(['twentytwentyfive'])
        ->and(SitePlugin::where('site_id', $site->id)->where('slug', 'removed-plugin')->exists())->toBeFalse();
});
