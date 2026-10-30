<?php

use App\Actions\NotifyOrganizationMembers;
use App\Jobs\SyncSiteStatusJob;
use App\Models\Site;
use App\Services\WordPressClientService;
use Illuminate\Support\Facades\Http;

// Replays a system-info response recorded from a real site running the
// agent plugin — catches drift between what the agent emits and what
// the sync expects. Re-record with:
//   curl -H "X-Agent-Token: <token>" https://<site>/wp-json/agent/v1/system-info
it('syncs a recorded real agent response', function () {
    $fixture = json_decode(
        file_get_contents(base_path('tests/fixtures/agent/system-info.json')),
        true,
    );

    expect($fixture)->toHaveKeys(['wp_version', 'php_version', 'plugins', 'themes']);

    $site = Site::factory()->create();

    Http::fake([
        '*/wp-json/agent/v1/system-info' => Http::response($fixture),
    ]);

    (new SyncSiteStatusJob($site))->handle(new WordPressClientService, app(NotifyOrganizationMembers::class));

    $site->refresh();

    expect($site->wp_version)->toBe($fixture['wp_version'])
        ->and($site->php_version)->toBe($fixture['php_version'])
        ->and($site->plugins->pluck('slug')->sort()->values()->all())
        ->toBe(collect($fixture['plugins'])->pluck('slug')->sort()->values()->all())
        ->and($site->themes->pluck('slug')->sort()->values()->all())
        ->toBe(collect($fixture['themes'])->pluck('slug')->sort()->values()->all());
});
