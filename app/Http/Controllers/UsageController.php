<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        abort_unless($organization instanceof Organization, 404);

        $credits = app(CreditService::class);
        $monthStart = now()->startOfMonth();

        // Cost estimates never leave the server — customers see credits only.
        $transactions = $organization->creditTransactions()
            ->with(['user:id,email', 'site:id,name'])
            ->latest('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'site_id', 'action', 'credits', 'quantity', 'description', 'created_at']);

        $usedThisMonth = $organization->creditTransactions()
            ->usage()
            ->where('created_at', '>=', $monthStart)
            ->sum('credits');

        $byAction = $credits->usageByAction(
            CreditTransaction::query()
                ->where('organization_id', $organization->id)
                ->where('created_at', '>=', $monthStart)
        );

        return Inertia::render('usage', [
            'credits' => [
                'balance' => $organization->credits_balance,
                'purchased' => $organization->credits_purchased,
                'allowance' => $credits->allowance($organization),
                'unlimited' => $organization->credits_unlimited,
                'resetsAt' => $organization->credits_reset_at?->toIso8601String(),
            ],
            'usedThisMonth' => -(int) $usedThisMonth,
            'byAction' => $byAction->map(fn (array $row) => [
                'action' => $row['action'],
                'units' => $row['units'],
                'credits' => $row['credits'],
            ])->values(),
            'transactions' => $transactions,
        ]);
    }
}
