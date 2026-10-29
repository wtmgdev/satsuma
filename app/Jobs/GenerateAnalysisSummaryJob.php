<?php

namespace App\Jobs;

use App\Models\SiteAnalysis;
use App\Services\AnalysisSummarizer;
use App\Services\CreditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAnalysisSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public SiteAnalysis $analysis,
        public bool $charged = false,
    ) {}

    public function handle(AnalysisSummarizer $summarizer): void
    {
        $summary = $summarizer->summarize($this->analysis);

        if ($summary === null) {
            Log::info('Analysis summary generation returned nothing.', [
                'analysis' => $this->analysis->id,
            ]);

            $this->refund();

            return;
        }

        $this->analysis->update(['summary' => $summary]);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Analysis summary job failed permanently.', [
            'analysis' => $this->analysis->id,
            'error' => $exception->getMessage(),
        ]);

        $this->refund();
    }

    private function refund(): void
    {
        if (! $this->charged || $this->analysis->site?->organization === null) {
            return;
        }

        app(CreditService::class)->refund(
            $this->analysis->site->organization,
            'ai_summary',
            site: $this->analysis->site,
        );
    }
}
