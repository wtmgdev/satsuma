<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * The credit cost of one unit of an action.
     */
    public function unitCost(string $action): int
    {
        return (int) config("credits.costs.{$action}", 1);
    }

    public function estimatedUnitCost(string $action): float
    {
        return (float) config("credits.estimated_unit_cost.{$action}", 0);
    }

    /**
     * The effective monthly allowance for an organization.
     */
    public function allowance(Organization $organization): int
    {
        return $organization->credits_allowance
            ?? (int) config('credits.default_allowance');
    }

    /**
     * Total spendable credits: monthly balance plus purchased credits.
     */
    public function available(Organization $organization): int
    {
        return $organization->credits_balance + $organization->credits_purchased;
    }

    /**
     * Dry run records usage but never enforces — local/testing only.
     */
    public function dryRun(): bool
    {
        return (bool) config('credits.dry_run')
            && app()->environment(['local', 'testing']);
    }

    /**
     * Charge credits for a set of actions, atomically. Refuses when the
     * balance is insufficient — the caller gets false and should surface that.
     *
     * @param  array<string, int>  $items  e.g. ['pagespeed_analysis' => 2, 'ai_summary' => 2]
     */
    public function charge(Organization $organization, array $items, ?User $user = null, ?Site $site = null): bool
    {
        return DB::transaction(function () use ($organization, $items, $user, $site) {
            $fresh = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->first();

            // Unlimited orgs (our own) still get a ledger entry — spend
            // analytics stay complete — but nothing is enforced.
            // Dry run (local dev) behaves the same way.
            if ($fresh?->credits_unlimited || $this->dryRun()) {
                foreach ($items as $action => $quantity) {
                    CreditTransaction::create([
                        'organization_id' => $organization->id,
                        'user_id' => $user?->id,
                        'site_id' => $site?->id,
                        'action' => $action,
                        'credits' => -($this->unitCost($action) * $quantity),
                        'quantity' => $quantity,
                        'cost_estimate' => round($this->estimatedUnitCost($action) * $quantity, 4),
                        'metered' => false,
                    ]);
                }

                return true;
            }

            $total = collect($items)
                ->sum(fn (int $quantity, string $action) => $this->unitCost($action) * $quantity);

            if ($this->available($fresh) < $total) {
                return false;
            }

            // Spend the monthly balance first, then purchased credits.
            $fromMonthly = min($fresh->credits_balance, $total);
            $fromPurchased = $total - $fromMonthly;

            $fresh->forceFill([
                'credits_balance' => $fresh->credits_balance - $fromMonthly,
                'credits_purchased' => $fresh->credits_purchased - $fromPurchased,
            ])->save();

            foreach ($items as $action => $quantity) {
                CreditTransaction::create([
                    'organization_id' => $organization->id,
                    'user_id' => $user?->id,
                    'site_id' => $site?->id,
                    'action' => $action,
                    'credits' => -($this->unitCost($action) * $quantity),
                    'quantity' => $quantity,
                    'cost_estimate' => round($this->estimatedUnitCost($action) * $quantity, 4),
                    'metered' => true,
                ]);
            }

            return true;
        });
    }

    /**
     * Refund a previously charged action (failed run). Credits go back to
     * the monthly balance; the refund nets out of usage reporting.
     */
    public function refund(Organization $organization, string $action, int $quantity = 1, ?Site $site = null, string $reason = 'Failed run'): void
    {
        $amount = $this->unitCost($action) * $quantity;

        DB::transaction(function () use ($organization, $action, $quantity, $amount, $site, $reason) {
            $fresh = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->first();

            $metered = ! ($fresh?->credits_unlimited || $this->dryRun());

            if ($metered) {
                $fresh->increment('credits_balance', $amount);
            }

            CreditTransaction::create([
                'organization_id' => $organization->id,
                'site_id' => $site?->id,
                'action' => $action,
                'credits' => $amount,
                'quantity' => $quantity,
                'cost_estimate' => -round($this->estimatedUnitCost($action) * $quantity, 4),
                'description' => "Refund — {$reason}",
                'metered' => $metered,
            ]);
        });
    }

    /**
     * Add purchased/granted credits — these never expire.
     */
    public function grant(Organization $organization, int $amount, string $action = 'topup', ?User $actor = null, ?string $description = null): void
    {
        DB::transaction(function () use ($organization, $amount, $action, $actor, $description) {
            $fresh = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->first();

            $fresh->increment('credits_purchased', $amount);

            CreditTransaction::create([
                'organization_id' => $organization->id,
                'user_id' => $actor?->id,
                'action' => $action,
                'credits' => $amount,
                'quantity' => 1,
                'description' => $description,
                'metered' => true,
            ]);
        });
    }

    /**
     * Give an organization its first allowance window (on creation).
     */
    public function initialize(Organization $organization): void
    {
        if ($organization->credits_reset_at !== null) {
            return;
        }

        $organization->forceFill([
            'credits_balance' => $this->allowance($organization),
            'credits_reset_at' => now()->addMonth(),
        ])->save();

        CreditTransaction::create([
            'organization_id' => $organization->id,
            'action' => 'monthly_reset',
            'credits' => $this->allowance($organization),
            'quantity' => 1,
            'description' => 'Initial allowance',
            'metered' => true,
        ]);
    }

    /**
     * Reset the monthly allowance for organizations whose window ended,
     * and initialize organizations that have no window yet.
     */
    public function resetDue(): int
    {
        $reset = 0;

        Organization::query()
            ->whereNull('credits_reset_at')
            ->eachById(function (Organization $organization) use (&$reset) {
                $this->initialize($organization);
                $reset++;
            });

        Organization::query()
            ->whereNotNull('credits_reset_at')
            ->where('credits_reset_at', '<=', now())
            ->eachById(function (Organization $organization) use (&$reset) {
                DB::transaction(function () use ($organization) {
                    $fresh = Organization::query()
                        ->whereKey($organization->id)
                        ->lockForUpdate()
                        ->first();

                    if ($fresh->credits_reset_at === null || $fresh->credits_reset_at->gt(now())) {
                        return;
                    }

                    // Leftover monthly credits expire — purchased credits don't.
                    if ($fresh->credits_balance > 0) {
                        CreditTransaction::create([
                            'organization_id' => $fresh->id,
                            'action' => 'monthly_expiry',
                            'credits' => -$fresh->credits_balance,
                            'quantity' => 1,
                            'description' => 'Unused monthly credits expired',
                            'metered' => true,
                        ]);
                    }

                    // Advance from the previous window so resets don't drift.
                    $resetAt = $fresh->credits_reset_at->copy();
                    do {
                        $resetAt = $resetAt->addMonthNoOverflow();
                    } while ($resetAt->lte(now()));

                    $fresh->forceFill([
                        'credits_balance' => $this->allowance($fresh),
                        'credits_reset_at' => $resetAt,
                    ])->save();

                    CreditTransaction::create([
                        'organization_id' => $fresh->id,
                        'action' => 'monthly_reset',
                        'credits' => $this->allowance($fresh),
                        'quantity' => 1,
                        'description' => 'Monthly allowance reset',
                        'metered' => true,
                    ]);
                });

                $reset++;
            });

        return $reset;
    }

    /**
     * Per-action usage totals for a transaction query: units consumed,
     * net credits spent and net estimated cost (refunds net out).
     */
    public function usageByAction(Builder $query): Collection
    {
        return $query->clone()
            ->usage()
            ->selectRaw('action, SUM(CASE WHEN credits < 0 THEN quantity ELSE -quantity END) as units, SUM(credits) as credits, SUM(cost_estimate) as cost')
            ->groupBy('action')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'units' => (int) $row->units,
                'credits' => -(int) $row->credits,
                'cost' => (float) $row->cost,
            ]);
    }
}
