<?php

namespace App\Policies;

use App\Models\OrgMembership;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, 'admin');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, 'owner');
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, 'admin');
    }

    private function hasRole(User $user, Organization $organization, string $role): bool
    {
        $membershipRole = $user->roleIn($organization);

        return $membershipRole !== null
            && (OrgMembership::ROLE_RANKS[$membershipRole] ?? 0) >= (OrgMembership::ROLE_RANKS[$role] ?? 99);
    }
}
