<?php

namespace App\Jobs;

use App\Models\SiteAnalysis;
use App\Services\AnalysisSummarizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateAnalysisSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public SiteAnalysis $analysis) {}

    public function handle(AnalysisSummarizer $summarizer): void
    {
        $summary = $summarizer->summarize($this->analysis);

        if ($summary === null) {
            Log::info('Analysis summary generation returned nothing.', [
                'analysis' => $this->analysis->id,
            ]);

            return;
        }

        $this->analysis->update(['summary' => $summary]);
    }
}
