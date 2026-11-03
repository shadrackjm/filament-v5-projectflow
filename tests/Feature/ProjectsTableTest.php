<?php

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->team = Team::factory()->create();
});

it('lists projects with their columns rendered', function () {
    $projects = Project::factory()->count(3)->create(['team_id' => $this->team->id]);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords($projects)
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('status')
        ->assertCanRenderTableColumn('tasks_count')
        ->assertCanRenderTableColumn('progress')
        ->assertCanRenderTableColumn('budget')
        ->assertCanRenderTableColumn('due_at');
});

it('sorts by budget', function () {
    $cheap = Project::factory()->create(['team_id' => $this->team->id, 'budget' => 1000]);
    $pricey = Project::factory()->create(['team_id' => $this->team->id, 'budget' => 90000]);

    Livewire::test(ListProjects::class)
        ->sortTable('budget', 'desc')
        ->assertCanSeeTableRecords([$pricey, $cheap], inOrder: true);
});

it('counts tasks in the tasks_count column', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);
    Task::factory()->count(4)->create(['project_id' => $project->id]);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$project])
        ->assertSee('4');
});

it('hides archived projects until the filter is changed', function () {
    $live = Project::factory()->create(['team_id' => $this->team->id, 'is_archived' => false]);
    $archived = Project::factory()->create(['team_id' => $this->team->id, 'is_archived' => true]);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$live])
        ->assertCanNotSeeTableRecords([$archived])
        ->filterTable('is_archived', true)
        ->assertCanSeeTableRecords([$archived])
        ->assertCanNotSeeTableRecords([$live]);
});

it('filters by status', function () {
    $active = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Active,
    ]);

    $planning = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Planning,
    ]);

    Livewire::test(ListProjects::class)
        ->filterTable('status', [ProjectStatus::Active->value])
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$planning]);
});

it('finds a project by searching its name', function () {
    $needle = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'name' => 'Lighthouse Rebuild',
    ]);

    $other = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'name' => 'Quiet Harbour',
    ]);

    Livewire::test(ListProjects::class)
        ->searchTable('Lighthouse')
        ->assertCanSeeTableRecords([$needle])
        ->assertCanNotSeeTableRecords([$other]);
});

it('shows only overdue projects when the overdue toggle is on', function () {
    $overdue = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Active,
        'due_at' => now()->subWeek(),
    ]);

    $onTrack = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Active,
        'due_at' => now()->addMonth(),
    ]);

    Livewire::test(ListProjects::class)
        ->filterTable('overdue')
        ->assertCanSeeTableRecords([$overdue])
        ->assertCanNotSeeTableRecords([$onTrack]);
});
