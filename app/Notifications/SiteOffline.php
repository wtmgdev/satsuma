<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteOffline extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Site $site)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->site->name} is offline")
            ->greeting("{$this->site->name} is offline")
            ->line("Shoal could not reach {$this->site->url} during its last connection check.")
            ->line('Possible causes: the site is down, the agent plugin is inactive, or the stored token no longer matches.')
            ->action('Open the site in Shoal', url("/sites/{$this->site->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "{$this->site->name} is offline",
            'body' => 'The site did not respond to its last connection check.',
            'site_id' => $this->site->id,
            'url' => "/sites/{$this->site->id}",
        ];
    }
}
