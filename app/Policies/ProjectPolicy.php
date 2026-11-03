<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
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

    public function view(User $user, Project $project): bool
    {
        return $user->belongsToTeam($project->team_id);
    }

    public function create(User $user): bool
    {
        return $user->hasTeamRole([TeamRole::Owner, TeamRole::Manager]);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasTeamRole([TeamRole::Owner, TeamRole::Manager], $project->team_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasTeamRole([TeamRole::Owner], $project->team_id);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
