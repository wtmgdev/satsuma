<?php

namespace App\Actions;

use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DispatchSiteJob
{
    /**
     * Queue a site job, degrading gracefully when the queue backend is down.
     */
    public function handle(Site $site, ShouldQueue $job): bool
    {
        try {
            Bus::dispatch($job);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
