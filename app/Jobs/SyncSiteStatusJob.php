<?php

namespace App\Jobs;

use App\ConnectionStatus;
use App\Models\Site;
use App\ScanStatus;
use App\Services\WordPressClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncSiteStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Site $site)
    {
        //
    }

    public function handle(WordPressClientService $client): void
    {
        $this->site->update(['scan_status' => ScanStatus::Running]);

        $inventory = $client->fetchSiteInventory($this->site);

        if ($inventory === []) {
            $this->site->update(['scan_status' => ScanStatus::Idle]);

            return;
        }

        DB::transaction(function () use ($inventory) {
            $this->site->update([
                'wp_version' => $inventory['wp_version'] ?? null,
                'php_version' => $inventory['php_version'] ?? null,
                'connection_status' => ConnectionStatus::Online,
                'last_ping_at' => now(),
                'last_scan_at' => now(),
                'scan_status' => ScanStatus::Idle,
            ]);

            $this->syncComponents($this->site->plugins(), $inventory['plugins'] ?? []);
            $this->syncComponents($this->site->themes(), $inventory['themes'] ?? []);
        });
    }

    /**
     * Upsert the returned components and remove ones no longer present on the site.
     */
    private function syncComponents($relation, array $components): void
    {
        $incoming = collect($components)
            ->filter(fn ($component) => is_array($component) && ! empty($component['slug']))
            ->mapWithKeys(fn ($component) => [$component['slug'] => $component]);

        $relation->whereNotIn('slug', $incoming->keys()->all())->delete();

        $relation->upsert(
            $incoming->map(fn ($component, $slug) => [
                'site_id' => $this->site->id,
                'name' => $component['name'] ?? $slug,
                'slug' => $slug,
                'version' => $component['version'] ?? null,
                'is_active' => (bool) ($component['is_active'] ?? false),
                'update_available' => (bool) ($component['update_available'] ?? false),
                'new_version' => $component['new_version'] ?? null,
            ])->values()->all(),
            uniqueBy: ['site_id', 'slug'],
            update: ['name', 'version', 'is_active', 'update_available', 'new_version'],
        );
    }

    public function failed(?\Throwable $exception): void
    {
        $this->site->update([
            'connection_status' => ConnectionStatus::Offline,
            'scan_status' => ScanStatus::Idle,
        ]);
    }
}
