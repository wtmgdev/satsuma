<?php

namespace App\Http\Controllers;

use App\Actions\DispatchSiteScan;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
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
                    'wp_version',
                    'php_version',
                    'last_ping_at',
                ])
            : collect();

        $clients = $organization
            ? $organization->clients()->orderBy('name')->get(['id', 'name'])
            : collect();

        return Inertia::render('sites/index', ['sites' => $sites, 'clients' => $clients]);
    }

    public function store(StoreSiteRequest $request, DispatchSiteScan $dispatchSiteScan): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        abort_unless($organization instanceof Organization, 403, 'Create an organization first.');

        $site = $organization->sites()->create([
            ...$request->validated(),
            'api_token' => $request->validated('api_token') ?: Str::random(64),
        ]);

        $queued = $dispatchSiteScan->handle($site);

        return redirect()->route('sites.show', $site)
            ->flashToast(
                $queued ? 'success' : 'warning',
                $queued ? 'Site created. First scan queued.' : 'Site created, but the scan queue is unavailable.',
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
            'agentToken' => $site->api_token,
        ]);
    }

    public function edit(Request $request, Site $site): Response
    {
        $this->authorize('update', $site);

        return Inertia::render('sites/edit', [
            'site' => $site->only(['id', 'client_id', 'name', 'url']),
            'clients' => $site->organization->clients()->orderBy('name')->get(['id', 'name']),
        ]);
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

    public function scan(Request $request, Site $site, DispatchSiteScan $dispatchSiteScan): RedirectResponse
    {
        $this->authorize('scan', $site);

        $queued = $dispatchSiteScan->handle($site);

        return back()->flashToast(
            $queued ? 'success' : 'warning',
            $queued ? "Scan for {$site->name} queued." : 'Queue unavailable — scan not queued.',
        );
    }
}
