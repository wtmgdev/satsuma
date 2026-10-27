<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Jobs\SyncSiteStatusJob;
use App\Models\Client;
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
        $sites = Site::query()
            ->with('client:id,name')
            ->orderBy('name')
            ->get([
                'id',
                'client_id',
                'name',
                'url',
                'connection_status',
                'wp_version',
                'php_version',
                'last_ping_at',
            ]);

        return Inertia::render('sites/index', ['sites' => $sites, 'clients' => Client::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = Site::create([
            ...$request->validated(),
            'api_token' => $request->validated('api_token') ?: Str::random(64),
        ]);

        SyncSiteStatusJob::dispatch($site);

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site created. First scan queued.');
    }

    public function show(Request $request, Site $site): Response
    {
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
        return Inertia::render('sites/edit', [
            'site' => $site->only(['id', 'client_id', 'name', 'url']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $site->update($data);

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site updated.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted.');
    }

    public function scan(Request $request, Site $site): RedirectResponse
    {
        SyncSiteStatusJob::dispatch($site);

        return back()->with('success', "Scan for {$site->name} queued.");
    }
}
