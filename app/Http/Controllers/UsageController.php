<?php

namespace App\Http\Controllers;

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

        $transactions = $organization->creditTransactions()
            ->with('user:id,email')
            ->latest('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'action', 'credits', 'quantity', 'cost_estimate', 'description', 'created_at']);

        $spend = $organization->creditTransactions()
            ->where('credits', '<', 0)
            ->where('created_at', '>=', $monthStart);

        $usedThisMonth = -(int) $spend->clone()->sum('credits');
        $estimatedCost = (float) $spend->clone()->sum('cost_estimate');

        $byAction = (clone $spend)->selectRaw('action, SUM(quantity) as units, SUM(credits) as credits, SUM(cost_estimate) as cost')
            ->groupBy('action')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'units' => (int) $row->units,
                'credits' => -(int) $row->credits,
                'cost' => (float) $row->cost,
            ]);

        return Inertia::render('usage', [
            'credits' => [
                'balance' => $organization->credits_balance,
                'allowance' => $credits->allowance($organization),
                'unlimited' => $organization->credits_unlimited,
                'resetsAt' => $organization->credits_reset_at?->toIso8601String(),
            ],
            'usedThisMonth' => $usedThisMonth,
            'estimatedCost' => $estimatedCost,
            'byAction' => $byAction,
            'transactions' => $transactions,
        ]);
    }
}
