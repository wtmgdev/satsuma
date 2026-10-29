<?php

use App\Jobs\GenerateAnalysisSummaryJob;
use App\Jobs\RunPageSpeedJob;
use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\Site;
use App\Models\SiteAnalysis;
use App\Models\User;
use App\Services\AnalysisSummarizer;
use App\Services\PageSpeedService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

function pagespeedOrg(int $balance, string $role = 'member'): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create([
        'credits_balance' => $balance,
        'credits_reset_at' => now()->addMonth(),
    ]);
    $organization->members()->attach($user->id, ['role' => $role]);
    $site = Site::factory()->for($organization)->create();

    return [$user, $organization, $site];
}

it('lets a plain member run PageSpeed and refuses a concurrent run', function () {
    Bus::fake();
    [$user, $organization, $site] = pagespeedOrg(10, 'member');

    $response = $this->actingAs($user)->post("/sites/{$site->id}/pagespeed");

    $response->assertRedirect();
    expect($organization->fresh()->credits_balance)->toBe(6)
        ->and(CreditTransaction::where('action', 'pagespeed_analysis')->where('credits', -2)->exists())->toBeTrue()
        ->and(CreditTransaction::where('action', 'ai_summary')->where('credits', -2)->exists())->toBeTrue();

    Bus::assertDispatched(RunPageSpeedJob::class, fn ($job) => $job->charged === true);

    // A second request while pending is refused and charges nothing.
    $this->actingAs($user)->post("/sites/{$site->id}/pagespeed")
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data', fn ($flash) => ($flash['toast']['type'] ?? null) === 'info'
            && str_contains($flash['toast']['message'] ?? '', 'already running'));

    expect($organization->fresh()->credits_balance)->toBe(6)
        ->and(CreditTransaction::count())->toBe(2);
});

it('refunds both credits when the PSI API fails', function () {
    config(['services.pagespeed.key' => 'test-key']);
    Http::fake(['googleapis.com/*' => Http::response('Server error', 500)]);

    [$user, $organization, $site] = pagespeedOrg(10);

    app(\App\Services\CreditService::class)->charge($organization, [
        'pagespeed_analysis' => 1,
        'ai_summary' => 1,
    ], $user, $site);

    $job = new RunPageSpeedJob($site, 'mobile', force: true, charged: true);
    $job->handle(app(PageSpeedService::class));

    expect($organization->fresh()->credits_balance)->toBe(10)
        ->and(CreditTransaction::where('action', 'pagespeed_analysis')->where('credits', 1)->exists())->toBeTrue()
        ->and(CreditTransaction::where('action', 'ai_summary')->where('credits', 1)->exists())->toBeTrue();
});

it('refunds the ai summary when the summarizer returns nothing', function () {
    [$user, $organization, $site] = pagespeedOrg(9);

    app(\App\Services\CreditService::class)->charge($organization, ['ai_summary' => 1], $user, $site);

    $analysis = SiteAnalysis::create([
        'site_id' => $site->id,
        'type' => 'pagespeed_mobile',
        'data' => ['strategy' => 'mobile', 'scores' => ['performance' => 80], 'metrics' => [], 'opportunities' => []],
        'score' => 80,
    ]);

    $summarizer = Mockery::mock(AnalysisSummarizer::class);
    $summarizer->shouldReceive('summarize')->andReturn(null);
    app()->instance(AnalysisSummarizer::class, $summarizer);

    (new GenerateAnalysisSummaryJob($analysis, charged: true))->handle($summarizer);

    expect($organization->fresh()->credits_balance)->toBe(9)
        ->and(CreditTransaction::where('action', 'ai_summary')->where('credits', 1)->exists())->toBeTrue();
});
