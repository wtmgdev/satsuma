<?php

namespace App\Jobs;

use App\ConnectionStatus;
use App\Models\Site;
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

    public function handle(WordPressClientService $client): void
    {
        $payload = $client->pingSite($this->site);

        if ($payload === null) {
            return;
        }

        $this->site->update([
            'wp_version' => $payload['wp_version'] ?? $this->site->wp_version,
            'php_version' => $payload['php_version'] ?? $this->site->php_version,
            'connection_status' => ConnectionStatus::Online,
            'last_ping_at' => now(),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->site->update(['connection_status' => ConnectionStatus::Offline]);
    }
}
