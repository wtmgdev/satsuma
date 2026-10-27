<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches RDAP data for the site's domain (registrar, dates, nameservers).
 */
class FetchWhoisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Site $site) {}

    public function handle(): void
    {
        $host = parse_url($this->site->url, PHP_URL_HOST);

        if (! $host) {
            return;
        }

        // Strip www. and walk back to the registrable domain (last two labels).
        $host = preg_replace('/^www\./', '', strtolower($host));
        $labels = explode('.', $host);
        $domain = implode('.', array_slice($labels, -2));

        try {
            $response = Http::timeout(15)->get("https://rdap.org/domain/{$domain}");

            if (! $response->successful()) {
                Log::info("whois lookup failed for {$domain}", ['status' => $response->status()]);

                return;
            }

            $data = $response->json();
            $events = collect($data['events'] ?? [])->keyBy('eventAction');

            $registrar = collect($data['entities'] ?? [])
                ->first(fn ($entity) => in_array('registrar', $entity['roles'] ?? []));

            $this->site->update([
                'whois' => [
                    'domain' => $domain,
                    'registrar' => collect($registrar['vcardArray'][1] ?? [])
                        ->firstWhere('0', 'fn')[3] ?? null,
                    'registered_at' => $events['registration']['eventDate'] ?? null,
                    'expires_at' => $events['expiration']['eventDate'] ?? null,
                    'updated_at' => $events['last changed']['eventDate'] ?? null,
                    'nameservers' => collect($data['nameservers'] ?? [])
                        ->pluck('ldhName')->values()->all(),
                ],
                'whois_checked_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
