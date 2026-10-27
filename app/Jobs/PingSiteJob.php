<?php

namespace App\Jobs;

use App\Actions\NotifyOrganizationMembers;
use App\ConnectionStatus;
use App\Models\Site;
use App\Notifications\SiteBackOnline;
use App\Notifications\SiteOffline;
use App\Services\WordPressClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PingSiteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Site $site)
    {
        //
    }

    public function handle(WordPressClientService $client, NotifyOrganizationMembers $notify): void
    {
        $wasOnline = $this->site->connection_status === ConnectionStatus::Online;

        $result = $client->pingSite($this->site);

        if (! ($result['ok'] ?? false)) {
            if ($wasOnline) {
                $notify->handle($this->site, new SiteOffline($this->site));
            }

            return;
        }

        $this->site->update([
            'wp_version' => $result['wp_version'] ?? $this->site->wp_version,
            'php_version' => $result['php_version'] ?? $this->site->php_version,
            'connection_status' => ConnectionStatus::Online,
            'last_ping_at' => now(),
        ]);

        if (! $wasOnline) {
            $notify->handle($this->site, new SiteBackOnline($this->site));
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->site->update(['connection_status' => ConnectionStatus::Offline]);
    }
}
