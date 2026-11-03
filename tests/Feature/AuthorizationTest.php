<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function memberOf(Team $team, TeamRole $role): User
{
    $user = User::factory()->create(['is_admin' => false]);
    $team->members()->attach($user, ['role' => $role->value]);

    return $user;
}

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
    ]);
});

it('keeps a user with no team out of the panel entirely', function () {
    $outsider = User::factory()->create(['is_admin' => false]);

    $this->actingAs($outsider)
        ->get('/admin')
        ->assertForbidden();
});

it('lets any team member into the panel', function () {
    $member = memberOf($this->team, TeamRole::Member);

    $this->actingAs($member)
        ->get('/admin/projects')
        ->assertOk();
});

it('hides the create button from members but shows it to managers', function () {
    Livewire::actingAs(memberOf($this->team, TeamRole::Member))
        ->test(ListProjects::class)
        ->assertActionHidden('create');

    Livewire::actingAs(memberOf($this->team, TeamRole::Manager))
        ->test(ListProjects::class)
        ->assertActionVisible('create');
});

it('lets managers edit but not members', function () {
    Livewire::actingAs(memberOf($this->team, TeamRole::Member))
        ->test(ListProjects::class)
        ->assertTableActionHidden('edit', $this->project);

    Livewire::actingAs(memberOf($this->team, TeamRole::Manager))
        ->test(ListProjects::class)
        ->assertTableActionVisible('edit', $this->project);
});

it('only lets owners delete a project', function () {
    Livewire::actingAs(memberOf($this->team, TeamRole::Manager))
        ->test(ListProjects::class)
        ->assertTableActionHidden('delete', $this->project);

    Livewire::actingAs(memberOf($this->team, TeamRole::Owner))
        ->test(ListProjects::class)
        ->assertTableActionVisible('delete', $this->project);
});

it('refuses to open another team\'s project', function () {
    $theirTeam = Team::factory()->create();
    $theirProject = Project::factory()->create(['team_id' => $theirTeam->id]);

    $member = memberOf($this->team, TeamRole::Owner);

    $this->actingAs($member)
        ->get("/admin/projects/{$theirProject->id}")
        ->assertForbidden();
});

it('won\'t let a member edit another team\'s task inline', function () {
    $theirTask = Task::factory()->create([
        'project_id' => Project::factory()->create(['team_id' => Team::factory()])->id,
        'is_billable' => true,
    ]);

    Livewire::actingAs(memberOf($this->team, TeamRole::Member))
        ->test(ListTasks::class)
        ->loadTable()
        ->call('updateTableColumnState', 'is_billable', (string) $theirTask->getKey(), false);

    expect($theirTask->refresh()->is_billable)->toBeTrue();
});

it('gives staff administrators everything via the policy before() hook', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    expect($admin->can('create', Project::class))->toBeTrue()
        ->and($admin->can('update', $this->project))->toBeTrue()
        ->and($admin->can('delete', $this->project))->toBeTrue();
});

it('never allows force deleting a project', function () {
    $owner = memberOf($this->team, TeamRole::Owner);

    expect($owner->can('forceDelete', $this->project))->toBeFalse();
});
