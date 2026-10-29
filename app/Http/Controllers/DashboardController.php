<?php

namespace App\Http\Controllers;

use App\Models\SitePlugin;
use App\Models\SiteTheme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        $sites = $organization
            ? $organization->sites()
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
                ])
            : collect();

        $pendingUpdates = $organization
            ? SitePlugin::where('update_available', true)
                    ->whereHas('site', fn ($query) => $query->where('organization_id', $organization->id))
                    ->count()
                + SiteTheme::where('update_available', true)
                    ->whereHas('site', fn ($query) => $query->where('organization_id', $organization->id))
                    ->count()
            : 0;

        return Inertia::render('dashboard', [
            'stats' => [
                'totalSites' => $sites->count(),
                'onlineSites' => $sites->where('connection_status', 'online')->count(),
                'offlineSites' => $sites->where('connection_status', 'offline')->count(),
                'pendingUpdates' => $pendingUpdates,
            ],
            'sites' => $sites,
            'clients' => $organization
                ? $organization->clients()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'agentPluginVersion' => AgentPluginController::version(),
        ]);
    }
}
