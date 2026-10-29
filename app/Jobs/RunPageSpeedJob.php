<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\SiteAnalysis;
use App\Services\CreditService;
use App\Services\PageSpeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPageSpeedJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 240;

    public int $tries = 2;

    public function __construct(
        public Site $site,
        public string $strategy = 'mobile',
        public bool $force = false,
        public bool $charged = false,
    ) {}

    public function handle(PageSpeedService $service): void
    {
        $type = "pagespeed_{$this->strategy}";

        // One run per site+strategy at a time — the API takes up to a minute.
        $lock = Cache::lock("pagespeed:{$this->site->id}:{$this->strategy}", 300);

        if (! $lock->get()) {
            $this->refund();

            return;
        }

        try {
            $latest = SiteAnalysis::latestFor($this->site, $type);

            if (! $this->force && $latest !== null && $latest->created_at->gt(now()->subDay())) {
                return;
            }

            $payload = $service->analyze($this->site, $this->strategy);

            if ($payload === null) {
                $this->refund();

                return;
            }

            $analysis = SiteAnalysis::create([
                'site_id' => $this->site->id,
                'type' => $type,
                'data' => $payload,
                'score' => $payload['scores']['performance'],
            ]);

            dispatch(new GenerateAnalysisSummaryJob($analysis, charged: $this->charged));
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('PageSpeed job failed permanently.', [
            'site' => $this->site->id,
            'strategy' => $this->strategy,
            'error' => $exception->getMessage(),
        ]);

        $this->refund();
    }

    /**
     * Return the prepaid credits: the analysis itself plus the AI summary
     * that won't run without a payload.
     */
    private function refund(): void
    {
        if (! $this->charged || $this->site->organization === null) {
            return;
        }

        $credits = app(CreditService::class);
        $credits->refund($this->site->organization, 'pagespeed_analysis', site: $this->site);
        $credits->refund($this->site->organization, 'ai_summary', site: $this->site);
    }
}
