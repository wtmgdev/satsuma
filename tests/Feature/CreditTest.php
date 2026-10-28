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

it('charges credits atomically and records the transaction', function () {
    [$organization, $owner] = orgWithCredits(10);

    $charged = app(CreditService::class)->charge($organization, 'pagespeed_analysis', 2, $owner);

    expect($charged)->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(8);

    $transaction = CreditTransaction::query()->latest('id')->first();
    expect($transaction->action)->toBe('pagespeed_analysis')
        ->and($transaction->credits)->toBe(-2)
        ->and($transaction->user_id)->toBe($owner->id)
        ->and((float) $transaction->cost_estimate)->toBeGreaterThan(0);
});

it('refuses to charge when the balance is insufficient', function () {
    [$organization] = orgWithCredits(1);

    $charged = app(CreditService::class)->charge($organization, 'ai_summary', 2);

    expect($charged)->toBeFalse()
        ->and($organization->fresh()->credits_balance)->toBe(1)
        ->and(CreditTransaction::count())->toBe(0);
});

it('records unlimited org usage without blocking or decrementing', function () {
    [$organization] = orgWithCredits(5);
    $organization->update(['credits_unlimited' => true]);

    $service = app(CreditService::class);

    expect($service->charge($organization, 'crawl_page', 500))->toBeTrue()
        ->and($organization->fresh()->credits_balance)->toBe(5)
        ->and(CreditTransaction::where('action', 'crawl_page')->where('credits', -500)->exists())->toBeTrue();

    // Even a zero balance never blocks an unlimited org.
    $organization->update(['credits_balance' => 0]);
    expect($service->charge($organization, 'ai_summary', 10))->toBeTrue();
});

it('grants credits and records the ledger entry', function () {
    [$organization] = orgWithCredits(0);

    app(CreditService::class)->grant($organization, 100, 'topup', 'Purchased pack');

    expect($organization->fresh()->credits_balance)->toBe(100)
        ->and(CreditTransaction::where('action', 'topup')->where('credits', 100)->exists())->toBeTrue();
});

it('initializes organizations without a reset window', function () {
    $organization = Organization::factory()->create(['credits_reset_at' => null, 'credits_balance' => 0]);

    $count = app(CreditService::class)->resetDue();

    expect($count)->toBe(1)
        ->and($organization->fresh()->credits_balance)->toBe((int) config('credits.default_allowance'))
        ->and($organization->fresh()->credits_reset_at)->toBeGreaterThan(now());
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
