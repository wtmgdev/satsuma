<?php

namespace App\Console\Commands;

use App\Actions\DispatchSiteJob;
use App\Jobs\FetchWhoisJob;
use App\Models\Site;
use Illuminate\Console\Command;

class RefreshWhois extends Command
{
    protected $signature = 'sites:whois';

    protected $description = 'Queue an RDAP domain-info refresh for every site';

    public function handle(DispatchSiteJob $dispatch): int
    {
        Site::query()
            ->where(function ($query) {
                $query->whereNull('whois_checked_at')
                    ->orWhere('whois_checked_at', '<', now()->subDay());
            })
            ->eachById(fn (Site $site) => $dispatch->handle($site, new FetchWhoisJob($site)));

        return self::SUCCESS;
    }
}
