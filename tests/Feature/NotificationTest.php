<?php

use App\Actions\NotifyOrganizationMembers;
use App\ConnectionStatus;
use App\Jobs\PingSiteJob;
use App\Jobs\SyncSiteStatusJob;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Notifications\CriticalVulnerabilitiesFound;
use App\Notifications\SiteBackOnline;
use App\Notifications\SiteOffline;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function siteWithMembers(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'member']]);
    $site = Site::factory()->for($organization)->create(['url' => 'https://example.test']);

    return [$site, $owner, $member, $outsider];
}

it('notifies org members when an online site goes offline', function () {
    Notification::fake();
    [$site, $owner, $member, $outsider] = siteWithMembers();
    $site->update(['connection_status' => ConnectionStatus::Online]);

    Http::fake(['example.test/*' => Http::response(null, 500)]);

    (new PingSiteJob($site))->handle(app(App\Services\WordPressClientService::class), app(NotifyOrganizationMembers::class));

    Notification::assertSentTo($owner, SiteOffline::class);
    Notification::assertSentTo($member, SiteOffline::class);
    Notification::assertNotSentTo($outsider, SiteOffline::class);
});

it('notifies org members when an offline site comes back online', function () {
    Notification::fake();
    [$site, $owner] = siteWithMembers();

    Http::fake(['example.test/*' => Http::response([
        'ok' => true, 'wp_version' => '6.9.4', 'php_version' => '8.1.34',
    ])]);

    (new PingSiteJob($site))->handle(app(App\Services\WordPressClientService::class), app(NotifyOrganizationMembers::class));

    Notification::assertSentTo($owner, SiteBackOnline::class);
    Notification::assertNotSentTo($owner, SiteOffline::class);
});

it('does not re-notify for already-notified vulnerabilities', function () {
    Notification::fake();
    [$site, $owner] = siteWithMembers();
    $site->update([
        'connection_status' => ConnectionStatus::Online,
        'notified_vulnerabilities' => ['plugin:akismet'],
    ]);

    Http::fake(['example.test/*' => Http::response(siteInventoryPayload())]);
    config(['services.vuln_feed.wordfence.token' => 'test-token']);
    Illuminate\Support\Facades\Cache::store('file')->forget('vulnfeed:index');

    file_put_contents(storage_path('app/vulnerabilities/wordfence-index.json'), json_encode([
        'plugin:akismet' => [[
            'id' => 'wf-1', 'title' => 'Akismet - SQLi', 'cve' => 'CVE-2026-1234', 'score' => 9.8,
            'from' => '5.0', 'from_inclusive' => true, 'to' => '5.3', 'to_inclusive' => true, 'patched' => '5.4',
        ]],
    ]));

    (new SyncSiteStatusJob($site))->handle(app(App\Services\WordPressClientService::class), app(NotifyOrganizationMembers::class));

    Notification::assertNotSentTo($owner, CriticalVulnerabilitiesFound::class);
    expect($site->refresh()->notified_vulnerabilities)->toBe(['plugin:akismet']);
});

it('notifies org members about fresh critical vulnerabilities', function () {
    Notification::fake();
    [$site, $owner, $member] = siteWithMembers();
    $site->update(['connection_status' => ConnectionStatus::Online]);

    Http::fake(['example.test/*' => Http::response(siteInventoryPayload())]);
    config(['services.vuln_feed.wordfence.token' => 'test-token']);
    Illuminate\Support\Facades\Cache::store('file')->forget('vulnfeed:index');

    file_put_contents(storage_path('app/vulnerabilities/wordfence-index.json'), json_encode([
        'plugin:akismet' => [[
            'id' => 'wf-1', 'title' => 'Akismet - SQLi', 'cve' => 'CVE-2026-1234', 'score' => 9.8,
            'from' => '5.0', 'from_inclusive' => true, 'to' => '5.3', 'to_inclusive' => true, 'patched' => '5.4',
        ]],
    ]));

    (new SyncSiteStatusJob($site))->handle(app(App\Services\WordPressClientService::class), app(NotifyOrganizationMembers::class));

    Notification::assertSentTo($owner, CriticalVulnerabilitiesFound::class);
    Notification::assertSentTo($member, CriticalVulnerabilitiesFound::class);
    expect($site->refresh()->notified_vulnerabilities)->toBe(['plugin:akismet']);
});
