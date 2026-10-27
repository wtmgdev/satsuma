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
     * Lightweight authenticated connection check. Returns a result array with
     * the agent payload on success, or a plain-language failure hint.
     */
    public function pingSite(Site $site): array
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

            if (is_array($payload) && ($payload['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'wp_version' => is_string($payload['wp_version'] ?? null) ? $payload['wp_version'] : null,
                    'php_version' => is_string($payload['php_version'] ?? null) ? $payload['php_version'] : null,
                ];
            }

            return ['ok' => false, 'hint' => 'Agent responded unexpectedly.'];
        } catch (ConnectionException|RequestException $e) {
            $site->update(['connection_status' => ConnectionStatus::Offline]);

            $status = $e instanceof RequestException ? $e->response?->status() : null;

            $hint = match (true) {
                $status === 404 => 'Agent plugin is missing or outdated — update it to the latest version.',
                in_array($status, [401, 403]) => 'Token mismatch — re-check the token stored in the agent settings.',
                $status !== null => "Agent responded with HTTP {$status}.",
                default => 'Site unreachable (connection error).',
            };

            Log::warning('Site ping failed.', [
                'site_id' => $site->id,
                'url' => $site->url,
                'http_status' => $status,
                'hint' => $hint,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'hint' => $hint];
        }
    }
}
