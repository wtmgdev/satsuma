<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'organizations' => $user
                ? $user->organizations()->orderBy('name')->get(['organizations.id', 'organizations.name', 'organizations.slug'])
                : [],
            'currentOrganization' => $request->attributes->get('currentOrganization'),
            'unreadNotifications' => $user ? $user->unreadNotifications()->count() : 0,
            'credits' => $this->credits($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function credits(Request $request): ?array
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization instanceof Organization) {
            return null;
        }

        return [
            'balance' => $organization->credits_balance,
            'purchased' => $organization->credits_purchased,
            'allowance' => app(CreditService::class)->allowance($organization),
            'unlimited' => $organization->credits_unlimited,
            'resetsAt' => $organization->credits_reset_at?->toIso8601String(),
        ];
    }
}
