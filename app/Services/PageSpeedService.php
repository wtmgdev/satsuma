<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PageSpeedService
{
    private const API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Lighthouse metric ids we surface, in display order.
     */
    private const METRICS = [
        'first-contentful-paint' => 'First Contentful Paint',
        'largest-contentful-paint' => 'Largest Contentful Paint',
        'total-blocking-time' => 'Total Blocking Time',
        'cumulative-layout-shift' => 'Cumulative Layout Shift',
        'speed-index' => 'Speed Index',
        'interactive' => 'Time to Interactive',
    ];

    /**
     * Run a Lighthouse analysis via the PSI API and return a compact payload.
     *
     * @return array{strategy: string, scores: array<string, int|null>, metrics: list<array{id: string, label: string, value: string, score: float|null}>, opportunities: list<array{title: string, savings: string}>}|null
     */
    public function analyze(Site $site, string $strategy = 'mobile'): ?array
    {
        $key = (string) config('services.pagespeed.key');

        if ($key === '') {
            Log::info('PageSpeed analysis skipped: no GOOGLE_PAGESPEED_API_KEY configured.');

            return null;
        }

        try {
            $response = Http::timeout(180)
                ->connectTimeout(10)
                ->get(self::API_URL, [
                    'url' => $site->url,
                    'strategy' => $strategy,
                    'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
                    'key' => $key,
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('PageSpeed analysis failed.', [
                'site' => $site->id,
                'strategy' => $strategy,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $this->parse($response->json(), $strategy);
    }

    /**
     * @return array{strategy: string, scores: array<string, int|null>, metrics: list<array{id: string, label: string, value: string, score: float|null}>, opportunities: list<array{title: string, savings: string}>}|null
     */
    public function parse(array $payload, string $strategy): ?array
    {
        $lighthouse = $payload['lighthouseResult'] ?? null;

        if (! is_array($lighthouse)) {
            return null;
        }

        $scores = [];
        foreach (['performance', 'accessibility', 'best-practices', 'seo'] as $category) {
            $score = $lighthouse['categories'][$category]['score'] ?? null;
            $scores[$category] = $score === null ? null : (int) round($score * 100);
        }

        $audits = $lighthouse['audits'] ?? [];

        $metrics = [];
        foreach (self::METRICS as $id => $label) {
            $audit = $audits[$id] ?? null;

            if (! is_array($audit)) {
                continue;
            }

            $metrics[] = [
                'id' => $id,
                'label' => $label,
                'value' => (string) ($audit['displayValue'] ?? '—'),
                'score' => isset($audit['score']) && is_numeric($audit['score'])
                    ? (float) $audit['score']
                    : null,
            ];
        }

        $opportunities = collect($audits)
            ->filter(fn ($audit) => is_array($audit)
                && ($audit['details']['type'] ?? null) === 'opportunity'
                && is_numeric($audit['numericValue'] ?? null)
                && $audit['numericValue'] > 0)
            ->sortByDesc('numericValue')
            ->take(5)
            ->map(fn ($audit) => [
                'title' => (string) $audit['title'],
                'savings' => (string) ($audit['displayValue'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'strategy' => $strategy,
            'scores' => $scores,
            'metrics' => $metrics,
            'opportunities' => $opportunities,
        ];
    }
}
