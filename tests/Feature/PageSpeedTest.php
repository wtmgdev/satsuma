<?php

use App\Models\Site;
use App\Services\PageSpeedService;

function pagespeedPayload(): array
{
    return [
        'lighthouseResult' => [
            'categories' => [
                'performance' => ['score' => 0.86],
                'accessibility' => ['score' => 0.94],
                'best-practices' => ['score' => 1.0],
                'seo' => ['score' => 0.92],
            ],
            'audits' => [
                'largest-contentful-paint' => [
                    'displayValue' => '2.4 s',
                    'score' => 0.7,
                    'numericValue' => 2400,
                ],
                'cumulative-layout-shift' => [
                    'displayValue' => '0.05',
                    'score' => 0.95,
                ],
                'unused-javascript' => [
                    'title' => 'Reduce unused JavaScript',
                    'displayValue' => 'Potential savings of 1,204 ms',
                    'numericValue' => 1204,
                    'details' => ['type' => 'opportunity'],
                ],
                'render-blocking-resources' => [
                    'title' => 'Eliminate render-blocking resources',
                    'displayValue' => 'Potential savings of 630 ms',
                    'numericValue' => 630,
                    'details' => ['type' => 'opportunity'],
                ],
                'uses-http2' => [
                    'title' => 'Does not use HTTP/2',
                    'displayValue' => 'Potential savings of 0 ms',
                    'numericValue' => 0,
                    'details' => ['type' => 'opportunity'],
                ],
            ],
        ],
    ];
}

it('parses the pagespeed payload into a compact structure', function () {
    $site = Site::factory()->create();
    $service = app(PageSpeedService::class);

    $result = $service->parse(pagespeedPayload(), 'mobile');

    expect($result['strategy'])->toBe('mobile')
        ->and($result['scores']['performance'])->toBe(86)
        ->and($result['scores']['seo'])->toBe(92)
        ->and($result['metrics'])->toHaveCount(2)
        ->and($result['metrics'][0]['value'])->toBe('2.4 s')
        ->and($result['opportunities'])->toHaveCount(2)
        ->and($result['opportunities'][0]['title'])->toBe('Reduce unused JavaScript');
});

it('stores analyses and skips fresh ones unless forced', function () {
    config(['services.pagespeed.key' => 'test-key']);
    Http::fake(['googleapis.com/*' => Http::response(pagespeedPayload())]);

    $site = Site::factory()->create();
    $job = new App\Jobs\RunPageSpeedJob($site, 'mobile');
    $job->handle(app(PageSpeedService::class));

    expect($site->analyses()->where('type', 'pagespeed_mobile')->count())->toBe(1);

    // Fresh analysis — no new API call
    $job->handle(app(PageSpeedService::class));
    expect($site->analyses()->where('type', 'pagespeed_mobile')->count())->toBe(1);

    // Forced — re-runs
    (new App\Jobs\RunPageSpeedJob($site, 'mobile', force: true))->handle(app(PageSpeedService::class));
    expect($site->analyses()->where('type', 'pagespeed_mobile')->count())->toBe(2);
});
