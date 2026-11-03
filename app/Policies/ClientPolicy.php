<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Admins bypass every check below.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->teams()->exists();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->belongsToTeam($client->team_id);
    }

    public function create(User $user): bool
    {
        return $user->hasTeamRole([TeamRole::Owner, TeamRole::Manager]);
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasTeamRole([TeamRole::Owner, TeamRole::Manager], $client->team_id);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasTeamRole([TeamRole::Owner], $client->team_id);
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->delete($user, $client);
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return false;
    }
}
