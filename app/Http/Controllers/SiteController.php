<?php

namespace App\Http\Controllers;

use App\Actions\DispatchSiteJob;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Jobs\PingSiteJob;
use App\Jobs\RunPageSpeedJob;
use App\Jobs\SyncSiteStatusJob;
use App\Models\Client;
use App\Models\Organization;
use App\Models\SiteAnalysis;
use App\Models\Site;
use App\ScanStatus;
use App\Services\CreditService;
use App\Services\VulnerabilityService;
use App\Services\VersionFeedService;
use App\Services\WordPressClientService;
use App\ConnectionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        $sites = $organization
            ? $organization->sites()
                ->with('client:id,name')
                ->orderBy('name')
                ->get([
                    'id',
                    'organization_id',
                    'client_id',
                    'name',
                    'url',
                    'connection_status',
                    'scan_status',
                    'wp_version',
                    'php_version',
                    'last_ping_at',
                    'last_scan_at',
                ])
            : collect();

        $clients = $organization
            ? $organization->clients()->orderBy('name')->get(['id', 'name'])
            : collect();

        $versionFeed = app(VersionFeedService::class);
        $versionInfo = $sites->mapWithKeys(
            fn (Site $site): array => [$site->id => $versionFeed->check($site)],
        )->all();

        return Inertia::render('sites/index', [
            'sites' => $sites,
            'clients' => $clients,
            'versionInfo' => $versionInfo,
            'agentPluginVersion' => AgentPluginController::version(),
        ]);
    }

    public function store(StoreSiteRequest $request, DispatchSiteJob $dispatchSiteJob): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        abort_unless($organization instanceof Organization, 403, 'Create an organization first.');

        $site = $organization->sites()->create([
            ...$request->validated(),
            'api_token' => $request->validated('api_token') ?: Str::random(64),
        ]);

        $dispatchSiteJob->handle($site, new PingSiteJob($site));
        $scanQueued = $dispatchSiteJob->handle($site, new SyncSiteStatusJob($site));

        if ($scanQueued) {
            $site->update(['scan_status' => ScanStatus::Queued]);
        }

        return redirect()->route('sites.show', $site)
            ->flashToast(
                $scanQueued ? 'success' : 'warning',
                $scanQueued
                    ? 'Site created. Testing connection, then scanning…'
                    : 'Site created, but the scan queue is unavailable.',
            );
    }

    public function show(Request $request, Site $site): Response
    {
        $this->authorize('view', $site);

        $site->load([
            'client:id,name,email,phone',
            'plugins:id,site_id,name,slug,version,is_active,update_available,new_version',
            'themes:id,site_id,name,slug,version,is_active,update_available,new_version',
        ]);

        return Inertia::render('sites/show', [
            'site' => $site,
            'clients' => $site->organization->clients()->orderBy('name')->get(['id', 'name']),
            'agentToken' => $site->api_token,
            'versionInfo' => app(VersionFeedService::class)->check($site),
            'agentPluginVersion' => AgentPluginController::version(),
            'vulnerabilities' => $this->vulnerabilitySummary($site),
            'analyses' => [
                'pagespeed_mobile' => SiteAnalysis::latestFor($site, 'pagespeed_mobile'),
                'pagespeed_desktop' => SiteAnalysis::latestFor($site, 'pagespeed_desktop'),
            ],
        ]);
    }

    public function runPageSpeed(Request $request, Site $site, DispatchSiteJob $dispatch, CreditService $credits): RedirectResponse
    {
        $this->authorize('update', $site);

        // 2 analyses + 2 AI summaries, charged upfront.
        if (! $credits->charge($site->organization, 'pagespeed_analysis', 2, $request->user())
            || ! $credits->charge($site->organization, 'ai_summary', 2, $request->user())
        ) {
            return redirect()->route('sites.show', $site)
                ->flashToast('error', 'Not enough credits — your organization is out of credits for this month.');
        }

        foreach (['mobile', 'desktop'] as $strategy) {
            $dispatch->handle($site, new RunPageSpeedJob($site, $strategy, force: true));
        }

        return redirect()->route('sites.show', $site)
            ->flashToast('info', 'PageSpeed analysis queued — results appear in the Performance tab in a minute or two.');
    }

    /**
     * Worst severity + count per component, keyed by "type:slug".
     */
    private function vulnerabilitySummary(Site $site): array
    {
        $vulnerabilities = app(VulnerabilityService::class)->forSite($site);

        return collect($vulnerabilities)->map(fn (array $found): array => [
            'severity' => VulnerabilityService::worstSeverity($found),
            'count' => count($found),
        ])->all();
    }


    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $this->authorize('update', $site);

        $data = $request->validated();

        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $site->update($data);

        return redirect()->route('sites.show', $site)
            ->flashToast('success', 'Site updated.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $this->authorize('delete', $site);

        $site->delete();

        return redirect()->route('sites.index')
            ->flashToast('success', 'Site deleted.');
    }

    public function scan(Request $request, Site $site, DispatchSiteJob $dispatchSiteJob): RedirectResponse
    {
        $this->authorize('scan', $site);

        $queued = $dispatchSiteJob->handle($site, new SyncSiteStatusJob($site));

        if ($queued) {
            $site->update(['scan_status' => ScanStatus::Queued]);
        }

        return back()->flashToast(
            $queued ? 'success' : 'warning',
            $queued ? "Scan for {$site->name} queued." : 'Queue unavailable — scan not queued.',
        );
    }

    public function ping(Request $request, Site $site): RedirectResponse
    {
        $this->authorize('scan', $site);

        $result = app(WordPressClientService::class)->pingSite($site);

        if ($result['ok']) {
            $site->update([
                'wp_version' => $result['wp_version'] ?? $site->wp_version,
                'php_version' => $result['php_version'] ?? $site->php_version,
                'connection_status' => ConnectionStatus::Online,
                'last_ping_at' => now(),
            ]);

            return back()->flashToast(
                'success',
                "Connected — WordPress {$result['wp_version']}, PHP {$result['php_version']}.",
            );
        }

        return back()->flashToast('error', "Connection failed: {$result['hint']}");
    }
}
