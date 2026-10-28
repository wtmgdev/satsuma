<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
     * Charge credits for an action, atomically. Refuses when the balance
     * is insufficient — the caller gets false and should surface that.
     *
     * @param  string  $action  e.g. pagespeed_analysis, ai_summary, crawl_page
     */
    public function charge(Organization $organization, string $action, int $quantity = 1, ?User $user = null, ?string $description = null): bool
    {
        $amount = $this->unitCost($action) * $quantity;

        return DB::transaction(function () use ($organization, $action, $quantity, $amount, $user, $description) {
            $fresh = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->first();

            // Unlimited orgs (our own) still get a ledger entry — spend
            // analytics stay complete — but nothing is enforced.
            // Dry run (local dev) behaves the same way.
            if ($fresh?->credits_unlimited || config('credits.dry_run')) {
                CreditTransaction::create([
                    'organization_id' => $organization->id,
                    'user_id' => $user?->id,
                    'action' => $action,
                    'credits' => -$amount,
                    'quantity' => $quantity,
                    'cost_estimate' => round($this->estimatedUnitCost($action) * $quantity, 4),
                    'description' => $description,
                ]);

                return true;
            }

            if (($fresh?->credits_balance ?? 0) < $amount) {
                return false;
            }

            $fresh->decrement('credits_balance', $amount);

            CreditTransaction::create([
                'organization_id' => $organization->id,
                'user_id' => $user?->id,
                'action' => $action,
                'credits' => -$amount,
                'quantity' => $quantity,
                'cost_estimate' => round($this->estimatedUnitCost($action) * $quantity, 4),
                'description' => $description,
            ]);

            return true;
        });
    }

    /**
     * Add credits without a balance check (top-ups, bonus grants).
     */
    public function grant(Organization $organization, int $amount, string $action = 'topup', ?string $description = null): void
    {
        DB::transaction(function () use ($organization, $amount, $action, $description) {
            $organization->increment('credits_balance', $amount);

            CreditTransaction::create([
                'organization_id' => $organization->id,
                'action' => $action,
                'credits' => $amount,
                'quantity' => 1,
                'description' => $description,
            ]);
        });
    }

    /**
     * Reset the monthly allowance for organizations whose window ended,
     * and initialize organizations that have no window yet.
     */
    public function resetDue(): int
    {
        $reset = 0;

        Organization::query()
            ->where(function ($query) {
                $query->whereNull('credits_reset_at')
                    ->orWhere('credits_reset_at', '<=', now());
            })
            ->eachById(function (Organization $organization) use (&$reset) {
                $this->initialize($organization);
                $reset++;
            });

        return $reset;
    }

    /**
     * Ensure an organization has an active reset window (on creation).
     */
    public function initialize(Organization $organization): void
    {
        if ($organization->credits_reset_at === null) {
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
            ]);
        } else {
            DB::transaction(function () use ($organization) {
                $fresh = Organization::query()
                    ->whereKey($organization->id)
                    ->lockForUpdate()
                    ->first();

                $fresh->forceFill([
                    'credits_balance' => $this->allowance($fresh),
                    'credits_reset_at' => now()->addMonth(),
                ])->save();

                CreditTransaction::create([
                    'organization_id' => $fresh->id,
                    'action' => 'monthly_reset',
                    'credits' => $this->allowance($fresh),
                    'quantity' => 1,
                    'description' => 'Monthly allowance reset',
                ]);
            });
        }
    }
}
