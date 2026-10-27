<?php

namespace App\Actions;

use App\Models\Site;
use App\Models\User;
use Illuminate\Notifications\Notification;

class NotifyOrganizationMembers
{
    /**
     * Send a notification to every member of the site's organization.
     */
    public function handle(Site $site, Notification $notification): void
    {
        $site->organization?->members()->each(
            fn (User $member) => $member->notify($notification),
        );
    }
}
