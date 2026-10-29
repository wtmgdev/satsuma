<?php

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Services\CreditService;

function orgWithCredits(int $balance): array
{
    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['credits_balance' => $balance, 'credits_reset_at' => now()->addMonth()]);
    $organization->members()->attach([$owner->id => ['role' => 'owner']]);
    $site = Site::factory()->for($organization)->create();

    return [$organization, $owner, $site];
}

function meteredSum(Organization $organization): int
{
    return (int) CreditTransaction::where('organization_id', $organization->id)
        ->where('metered', true)
        ->sum('credits');
}

it('charges credits atomically and records the transaction', function () {
    [$organization, $owner] = orgWithCredits(10);

    $charged = app(CreditService::class)->charge($organization, ['pagespeed_analysis' => 2], $owner);

    expect($charged)->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(8);

    $transaction = CreditTransaction::query()->latest('id')->first();
    expect($transaction->action)->toBe('pagespeed_analysis')
        ->and($transaction->credits)->toBe(-2)
        ->and($transaction->user_id)->toBe($owner->id)
        ->and($transaction->metered)->toBeTrue()
        ->and((float) $transaction->cost_estimate)->toBeGreaterThan(0);
});

it('refuses to charge when the balance is insufficient', function () {
    [$organization] = orgWithCredits(1);

    $charged = app(CreditService::class)->charge($organization, ['ai_summary' => 2]);

    expect($charged)->toBeFalse()
        ->and($organization->fresh()->credits_balance)->toBe(1)
        ->and(CreditTransaction::count())->toBe(0);
});

it('treats a multi-item charge as all-or-nothing', function () {
    [$organization] = orgWithCredits(3);

    $charged = app(CreditService::class)->charge($organization, [
        'pagespeed_analysis' => 2,
        'ai_summary' => 2,
    ]);

    expect($charged)->toBeFalse()
        ->and($organization->fresh()->credits_balance)->toBe(3)
        ->and(CreditTransaction::count())->toBe(0);
});

it('spends the monthly balance before purchased credits', function () {
    [$organization] = orgWithCredits(2);
    $organization->update(['credits_purchased' => 5]);

    $charged = app(CreditService::class)->charge($organization, ['crawl_page' => 4]);

    expect($charged)->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(0)
        ->and($organization->fresh()->credits_purchased)->toBe(3);
});

it('records unlimited org usage without blocking or decrementing', function () {
    [$organization] = orgWithCredits(5);
    $organization->update(['credits_unlimited' => true]);

    $service = app(CreditService::class);

    expect($service->charge($organization, ['crawl_page' => 500]))->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(5)
        ->and(CreditTransaction::where('action', 'crawl_page')->where('credits', -500)->where('metered', false)->exists())->toBeTrue();

    // Even a zero balance never blocks an unlimited org.
    $organization->update(['credits_balance' => 0]);
    expect($service->charge($organization, ['ai_summary' => 10]))->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(0);
});

it('grants purchased credits and records the actor', function () {
    [$organization, $owner] = orgWithCredits(0);
    $admin = User::factory()->create();

    app(CreditService::class)->grant($organization, 100, 'topup', $admin, 'Purchased pack');

    expect($organization->fresh()->credits_balance)->toBe(0)
        ->and($organization->fresh()->credits_purchased)->toBe(100);

    $transaction = CreditTransaction::where('action', 'topup')->first();
    expect($transaction->credits)->toBe(100)
        ->and($transaction->user_id)->toBe($admin->id)
        ->and($transaction->metered)->toBeTrue();
});

it('initializes organizations without a reset window', function () {
    $organization = Organization::factory()->create(['credits_reset_at' => null, 'credits_balance' => 0]);

    $count = app(CreditService::class)->resetDue();

    expect($count)->toBe(1)
        ->and($organization->fresh()->credits_balance)->toBe((int) config('credits.default_allowance'))
        ->and($organization->fresh()->credits_reset_at)->toBeGreaterThan(now());
});

it('gives new organizations their allowance on creation', function () {
    $user = User::factory()->create();

    $organization = Organization::createWithOwner($user, 'Fresh Org');

    expect($organization->fresh()->credits_balance)->toBe((int) config('credits.default_allowance'))
        ->and($organization->fresh()->credits_reset_at)->toBeGreaterThan(now())
        ->and(CreditTransaction::where('organization_id', $organization->id)
            ->where('action', 'monthly_reset')
            ->where('credits', config('credits.default_allowance'))
            ->exists())->toBeTrue();
});

it('expires leftover monthly credits but keeps purchased credits on reset', function () {
    $service = app(CreditService::class);

    // Seed balances through ledger rows so the metered-sum invariant holds.
    $organization = Organization::factory()->create(['credits_balance' => 0, 'credits_reset_at' => null]);
    $service->initialize($organization);
    $service->grant($organization, 40);
    $service->charge($organization, ['crawl_page' => ((int) config('credits.default_allowance')) - 7]);

    $resetAt = now()->subMonth()->startOfDay();
    $organization->forceFill(['credits_reset_at' => $resetAt])->save();

    expect($organization->fresh()->credits_balance)->toBe(7)
        ->and($organization->fresh()->credits_purchased)->toBe(40);

    $service->resetDue();

    $fresh = $organization->fresh();

    expect($fresh->credits_balance)->toBe((int) config('credits.default_allowance'))
        ->and($fresh->credits_purchased)->toBe(40)
        ->and(CreditTransaction::where('action', 'monthly_expiry')->where('credits', -7)->exists())->toBeTrue()
        ->and(CreditTransaction::where('action', 'monthly_reset')->where('credits', config('credits.default_allowance'))->exists())->toBeTrue()
        // Advanced from the previous window, not from now — no drift.
        ->and($fresh->credits_reset_at->toDateTimeString())->toBe((function () use ($resetAt) {
            $date = $resetAt->copy();
            do {
                $date = $date->addMonthNoOverflow();
            } while ($date->lte(now()));

            return $date->toDateTimeString();
        })())
        // Ledger invariant: metered sum == balance + purchased.
        ->and(meteredSum($organization))->toBe($fresh->credits_balance + $fresh->credits_purchased);
});

it('refunds to the monthly balance and nets out of usage reporting', function () {
    [$organization, $owner, $site] = orgWithCredits(10);
    $service = app(CreditService::class);

    $service->charge($organization, ['pagespeed_analysis' => 1], $owner, $site);
    $service->refund($organization, 'pagespeed_analysis', site: $site);

    expect($organization->fresh()->credits_balance)->toBe(10);

    $usage = $service->usageByAction(CreditTransaction::query()->where('organization_id', $organization->id));

    expect($usage->firstWhere('action', 'pagespeed_analysis'))->toMatchArray([
        'units' => 0,
        'credits' => 0,
        'cost' => 0.0,
    ]);
});

it('honours dry run in testing but ignores it in production', function () {
    config(['credits.dry_run' => true]);
    [$organization] = orgWithCredits(5);
    $service = app(CreditService::class);

    expect($service->charge($organization, ['crawl_page' => 100]))->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(5)
        ->and(CreditTransaction::where('metered', false)->exists())->toBeTrue();

    app()->detectEnvironment(fn () => 'production');

    expect($service->charge($organization, ['crawl_page' => 100]))->toBeFalse()
        ->and($organization->fresh()->credits_balance)->toBe(5)
        ->and(CreditTransaction::where('metered', false)->count())->toBe(1);

    app()->detectEnvironment(fn () => 'testing');
});

it('keeps the ledger rows when the organization is deleted', function () {
    [$organization, $owner, $site] = orgWithCredits(10);

    app(CreditService::class)->charge($organization, ['ai_summary' => 1], $owner, $site);

    $ids = CreditTransaction::pluck('id');

    $organization->delete();

    expect(CreditTransaction::whereIn('id', $ids)->count())->toBe(1)
        ->and(CreditTransaction::whereIn('id', $ids)->first()->organization_id)->toBeNull();
});

it('blocks the pagespeed run when the organization is out of credits', function () {
    [$organization, $owner, $site] = orgWithCredits(0);

    $response = $this
        ->actingAs($owner)
        ->post("/sites/{$site->id}/pagespeed");

    $response->assertRedirect()
        ->assertSessionHas('inertia.flash_data', fn ($flash) => ($flash['toast']['type'] ?? null) === 'error'
            && str_contains($flash['toast']['message'] ?? '', 'Not enough credits'));
});
