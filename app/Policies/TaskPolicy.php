<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
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

    public function view(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team_id);
    }

    public function create(User $user): bool
    {
        return $user->teams()->exists();
    }

    /**
     * Anyone on the team can update a task — that is the point of a task board.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->belongsToTeam($task->project->team_id);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasTeamRole([TeamRole::Owner, TeamRole::Manager], $task->project->team_id);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
