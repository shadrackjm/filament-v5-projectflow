<?php

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->team = Team::factory()->create();
});

it('shows the project name, status and budget on the view page', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Lighthouse Rebuild',
        'status' => ProjectStatus::Active,
        'budget' => 42000,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getKey()])
        ->assertSee('Lighthouse Rebuild')
        ->assertSee('Active')
        ->assertSee('$42,000.00');
});

it('summarises task counts and logged time', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    $done = Task::factory()->count(2)->create([
        'project_id' => $project->id,
        'completed_at' => now(),
    ]);

    Task::factory()->count(3)->create([
        'project_id' => $project->id,
        'completed_at' => null,
    ]);

    $done->first()->timeLogs()->create([
        'user_id' => auth()->id(),
        'minutes' => 90,
        'logged_on' => today(),
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getKey()])
        ->assertSee('2 of 5')
        ->assertSee('1.5 hrs');
});

it('falls back to placeholders when optional data is missing', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'client_id' => null,
        'lead_id' => null,
        'budget' => null,
        'due_at' => null,
        'description' => null,
    ]);

    Livewire::test(ViewProject::class, ['record' => $project->getKey()])
        ->assertSee('No client')
        ->assertSee('Unassigned')
        ->assertSee('No budget set')
        ->assertSee('No deadline');
});

it('warns when an active project is past its deadline', function () {
    $overdue = Project::factory()->create([
        'team_id' => $this->team->id,
        'status' => ProjectStatus::Active,
        'due_at' => now()->subMonth(),
    ]);

    $healthy = Project::factory()->create([
        'team_id' => $this->team->id,
        'status' => ProjectStatus::Active,
        'due_at' => now()->addMonth(),
    ]);

    Livewire::test(ViewProject::class, ['record' => $overdue->getKey()])
        ->assertSee('Overdue projects still bill');

    Livewire::test(ViewProject::class, ['record' => $healthy->getKey()])
        ->assertDontSee('Overdue projects still bill');
});
