<?php

namespace App\Actions;

use App\Jobs\SyncSiteStatusJob;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DispatchSiteScan
{
    /**
     * Queue a site scan, degrading gracefully when the queue backend is down.
     */
    public function handle(Site $site): bool
    {
        try {
            Bus::dispatch(new SyncSiteStatusJob($site));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
