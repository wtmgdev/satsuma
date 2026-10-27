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
}
