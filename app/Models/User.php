<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'job_title', 'avatar_path', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory,
        InteractsWithAppAuthentication,
        InteractsWithAppAuthenticationRecovery,
        Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Getting *in* is coarse: staff administrators, or anyone who belongs to a team.
     * What you can actually do once inside is decided by the policies.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->is_admin || $this->teams()->exists(),
            default => $this->teams()->exists(),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Team permissions — the vocabulary our policies are written in
    |--------------------------------------------------------------------------
    */

    public function belongsToTeam(?int $teamId): bool
    {
        if (blank($teamId)) {
            return false;
        }

        return $this->teams()->whereKey($teamId)->exists();
    }

    /**
     * Does this user hold one of these roles — on a given team, or on any team?
     *
     * @param  array<int, TeamRole>  $roles
     */
    public function hasTeamRole(array $roles, ?int $teamId = null): bool
    {
        $values = array_map(fn (TeamRole $role): string => $role->value, $roles);

        return $this->teams()
            ->when($teamId, fn ($query) => $query->whereKey($teamId))
            ->wherePivotIn('role', $values)
            ->exists();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ledProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'lead_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }
}
