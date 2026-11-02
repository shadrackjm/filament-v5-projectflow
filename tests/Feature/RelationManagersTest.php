<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\RelationManagers\ProjectsRelationManager;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\RelationManagers\MembersRelationManager;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
    $this->team = Team::factory()->create();
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

it('lists a project\'s tasks in its relation manager', function () {
    $tasks = Task::factory()->count(3)->create(['project_id' => $this->project->id]);
    $elsewhere = Task::factory()->create();

    Livewire::test(TasksRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])
        ->assertCanSeeTableRecords($tasks)
        ->assertCanNotSeeTableRecords([$elsewhere]);
});

it('attaches a user to a team with a pivot role', function () {
    $user = User::factory()->create();

    Livewire::test(MembersRelationManager::class, [
        'ownerRecord' => $this->team,
        'pageClass' => EditTeam::class,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $user->id,
            'role' => TeamRole::Manager->value,
        ])
        ->assertHasNoTableActionErrors();

    expect($this->team->refresh()->members)->toHaveCount(1)
        ->and($this->team->members->first()->pivot->role)->toBe(TeamRole::Manager->value);
});

it('scopes a client\'s projects to that client', function () {
    $client = Client::factory()->create(['team_id' => $this->team->id]);
    $this->project->update(['client_id' => $client->id]);
    $elsewhere = Project::factory()->create(['team_id' => $this->team->id]);

    Livewire::test(ProjectsRelationManager::class, [
        'ownerRecord' => $client,
        'pageClass' => ViewClient::class,
    ])
        ->assertCanSeeTableRecords([$this->project])
        ->assertCanNotSeeTableRecords([$elsewhere]);
});
