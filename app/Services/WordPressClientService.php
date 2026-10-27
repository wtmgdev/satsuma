<?php

namespace App\Services;

use App\ConnectionStatus;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressClientService
{
    public function fetchSiteInventory(Site $site): array
    {
        try {
            $response = Http::baseUrl(rtrim($site->url, '/'))
                ->acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Agent-Token' => $site->api_token,
                ])
                ->get('/wp-json/agent/v1/system-info');

            return $response->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            $site->update(['connection_status' => ConnectionStatus::Offline]);

            Log::warning('Site inventory fetch failed.', [
                'site_id' => $site->id,
                'url' => $site->url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Lightweight authenticated connection check. Returns the ping payload
     * (versions included) or null when the site is unreachable/unauthorized.
     */
    public function pingSite(Site $site): ?array
    {
        try {
            $response = Http::baseUrl(rtrim($site->url, '/'))
                ->acceptJson()
                ->timeout(5)
                ->connectTimeout(3)
                ->withHeaders([
                    'X-Agent-Token' => $site->api_token,
                ])
                ->get('/wp-json/agent/v1/ping');

            $payload = $response->throw()->json();

            return is_array($payload) && ($payload['ok'] ?? false) === true ? $payload : null;
        } catch (ConnectionException|RequestException $e) {
            $site->update(['connection_status' => ConnectionStatus::Offline]);

            $status = $e instanceof RequestException ? $e->response?->status() : null;

            Log::warning('Site ping failed.', [
                'site_id' => $site->id,
                'url' => $site->url,
                'http_status' => $status,
                'hint' => match (true) {
                    $status === 404 => 'Agent plugin is missing or outdated — no ping route.',
                    in_array($status, [401, 403]) => 'Token mismatch — re-check the token stored in the agent settings.',
                    $status !== null => 'Agent responded with an error.',
                    default => 'Site unreachable (connection error).',
                },
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
