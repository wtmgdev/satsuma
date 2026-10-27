<?php

namespace App\Console\Commands;

use App\Actions\DispatchSiteJob;
use App\Jobs\PingSiteJob;
use App\Models\Site;
use Illuminate\Console\Command;

class PingSites extends Command
{
    protected $signature = 'sites:ping';

    protected $description = 'Queue a connection ping for every site';

    public function handle(DispatchSiteJob $dispatch): int
    {
        Site::query()->eachById(function (Site $site) use ($dispatch) {
            $dispatch->handle($site, new PingSiteJob($site));
        });

        return self::SUCCESS;
    }
}
