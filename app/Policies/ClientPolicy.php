<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\OrgMembership;
use App\Models\Organization;
use App\Models\User;

class ClientPolicy
{
    public function view(User $user, Client $client): bool
    {
        return $user->roleIn($client->organization) !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->hasRole($user, $client->organization, 'admin');
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->hasRole($user, $client->organization, 'admin');
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
