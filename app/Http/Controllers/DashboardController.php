<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SitePlugin;
use App\Models\SiteTheme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
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

        $pendingUpdates = SitePlugin::where('update_available', true)->count()
            + SiteTheme::where('update_available', true)->count();

        return Inertia::render('dashboard', [
            'stats' => [
                'totalSites' => $sites->count(),
                'onlineSites' => $sites->where('connection_status', 'online')->count(),
                'offlineSites' => $sites->where('connection_status', 'offline')->count(),
                'pendingUpdates' => $pendingUpdates,
            ],
            'sites' => $sites,
        ]);
    }
}
