<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Filament\Widgets\OverdueTasksTable;
use App\Filament\Widgets\StudioOverview;
use App\Filament\Widgets\TasksByStatusChart;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('counts active projects and overdue tasks', function () {
    $team = Team::factory()->create();

    $active = Project::factory()->count(2)->create([
        'team_id' => $team->id,
        'status' => ProjectStatus::Active,
    ]);

    Project::factory()->create(['team_id' => $team->id, 'status' => ProjectStatus::Planning]);

    Task::factory()->count(3)->create([
        'project_id' => $active->first()->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
    ]);

    Livewire::test(StudioOverview::class)
        ->assertSee('Active projects')
        ->assertSee('Overdue tasks')
        ->assertSee('1 still in planning')
        ->assertSee('Needs attention today');
});

it('scopes the stats to the dashboard team filter', function () {
    $ours = Team::factory()->create();
    $theirs = Team::factory()->create();

    Project::factory()->count(2)->create(['team_id' => $ours->id, 'status' => ProjectStatus::Active]);
    Project::factory()->count(5)->create(['team_id' => $theirs->id, 'status' => ProjectStatus::Active]);

    // Read the "Active projects" stat itself — assertSee('2') also matches a 2 anywhere in the HTML.
    $activeProjects = fn (array $pageFilters = []): mixed => invade(
        Livewire::test(StudioOverview::class, ['pageFilters' => $pageFilters])->instance()
    )->getCachedStats()[0]->getValue();

    expect($activeProjects())->toBe(7)
        ->and($activeProjects(['team_id' => $ours->id]))->toBe(2);
});

it('builds one chart slice per task status', function () {
    $project = Project::factory()->create(['team_id' => Team::factory()]);

    Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => TaskStatus::Todo]);
    Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done]);

    // getCachedData() is protected; spatie/invade ships with Filament and lets us read it.
    $data = invade(Livewire::test(TasksByStatusChart::class)->instance())->getCachedData();

    expect($data['labels'])->toBe(['To do', 'In progress', 'In review', 'Done'])
        ->and($data['datasets'][0]['data'])->toBe([2, 0, 0, 1]);
});

it('lists only overdue, incomplete tasks in the table widget', function () {
    $project = Project::factory()->create(['team_id' => Team::factory()]);

    $overdue = Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
    ]);

    $doneButLate = Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => now(),
    ]);

    $future = Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->addWeek(),
        'completed_at' => null,
    ]);

    Livewire::test(OverdueTasksTable::class)
        ->assertCanSeeTableRecords([$overdue])
        ->assertCanNotSeeTableRecords([$doneButLate, $future]);
});

it('completes a task straight from the overdue widget', function () {
    $project = Project::factory()->create(['team_id' => Team::factory()]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
        'status' => TaskStatus::Todo,
    ]);

    Livewire::test(OverdueTasksTable::class)
        ->callTableAction('complete', $task)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Task completed');

    expect($task->refresh()->status)->toBe(TaskStatus::Done)
        ->and($task->completed_at)->not->toBeNull();
});
