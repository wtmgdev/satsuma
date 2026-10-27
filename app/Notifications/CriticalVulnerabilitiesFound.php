<?php

namespace App\Notifications;

use App\Models\Site;
use App\Services\VulnerabilityFeed\Vulnerability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalVulnerabilitiesFound extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, list<Vulnerability>>  $vulnerabilities  keyed by "type:slug"
     */
    public function __construct(public Site $site, public array $vulnerabilities)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Critical vulnerabilities on {$this->site->name}")
            ->greeting("Critical vulnerabilities on {$this->site->name}")
            ->line('New critical or high severity vulnerabilities affect components on this site:');

        foreach ($this->vulnerabilities as $key => $found) {
            [$type, $slug] = explode(':', $key);

            foreach ($found as $vulnerability) {
                $mail->line(
                    "{$type} {$slug}: {$vulnerability->title} ({$vulnerability->severity}".
                    ($vulnerability->cve ? ", {$vulnerability->cve}" : '').
                    ($vulnerability->patchedVersion ? ", fixed in {$vulnerability->patchedVersion}" : ', no patched release yet').')'
                );
            }
        }

        return $mail
            ->line('Update the affected components or plan a patch window.')
            ->action('Open the site in Shoal', url("/sites/{$this->site->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Critical vulnerabilities on {$this->site->name}",
            'body' => count($this->vulnerabilities).' component(s) with critical or high severity issues.',
            'site_id' => $this->site->id,
            'url' => "/sites/{$this->site->id}",
            'vulnerabilities' => collect($this->vulnerabilities)
                ->map(fn (array $found, string $key) => [
                    'component' => $key,
                    'severity' => \App\Services\VulnerabilityService::worstSeverity($found),
                    'count' => count($found),
                ])->values()->all(),
        ];
    }
}
