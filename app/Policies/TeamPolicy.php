<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
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

    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team->id);
    }

    /**
     * New teams are set up by staff — the before() hook lets admins through.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasTeamRole([TeamRole::Owner], $team->id);
    }

    public function delete(User $user, Team $team): bool
    {
        return false;
    }

    public function restore(User $user, Team $team): bool
    {
        return false;
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return false;
    }
}
