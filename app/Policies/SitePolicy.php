<?php

namespace App\Policies;

use App\Models\OrgMembership;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function view(User $user, Site $site): bool
    {
        return $user->roleIn($site->organization) !== null;
    }

    public function update(User $user, Site $site): bool
    {
        return $this->hasRole($user, $site->organization, 'admin');
    }

    public function delete(User $user, Site $site): bool
    {
        return $this->hasRole($user, $site->organization, 'admin');
    }

    public function scan(User $user, Site $site): bool
    {
        return $user->roleIn($site->organization) !== null;
    }

    private function hasRole(User $user, ?Organization $organization, string $role): bool
    {
        if ($organization === null) {
            return false;
        }

        $membershipRole = $user->roleIn($organization);

        return $membershipRole !== null
            && (OrgMembership::ROLE_RANKS[$membershipRole] ?? 0) >= (OrgMembership::ROLE_RANKS[$role] ?? 99);
    }
}
