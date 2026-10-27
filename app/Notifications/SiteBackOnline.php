<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SiteBackOnline extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Site $site)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "{$this->site->name} is back online",
            'body' => 'The site responded to its latest connection check.',
            'site_id' => $this->site->id,
            'url' => "/sites/{$this->site->id}",
        ];
    }
}
