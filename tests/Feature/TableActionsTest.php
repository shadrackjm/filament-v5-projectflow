<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Tasks\Pages\ListTasks;
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
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Active,
    ]);
});

it('marks a project completed through the confirmation action', function () {
    Livewire::test(ListProjects::class)
        ->callTableAction('complete', $this->project)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Project completed');

    expect($this->project->refresh()->status)->toBe(ProjectStatus::Completed);
});

it('hides the complete action on a project that is already completed', function () {
    $done = Project::factory()->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Completed,
    ]);

    Livewire::test(ListProjects::class)
        ->assertTableActionHidden('complete', $done)
        ->assertTableActionVisible('complete', $this->project);
});

it('replicates a project with a new slug', function () {
    Livewire::test(ListProjects::class)
        ->callTableAction('replicate', $this->project)
        ->assertHasNoTableActionErrors();

    expect(Project::query()->where('name', $this->project->name.' (copy)')->exists())->toBeTrue();
});

it('changes the status of many projects at once', function () {
    $projects = Project::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'is_archived' => false,
        'status' => ProjectStatus::Planning,
    ]);

    Livewire::test(ListProjects::class)
        ->callTableBulkAction('setStatus', $projects, ['status' => ProjectStatus::OnHold->value])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Status updated');

    expect(Project::query()->whereIn('id', $projects->pluck('id'))->pluck('status')->unique()->all())
        ->toBe([ProjectStatus::OnHold]);
});

it('logs time against a task from a modal form', function () {
    $task = Task::factory()->create(['project_id' => $this->project->id]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->callTableAction('logTime', $task, [
            'minutes' => 120,
            'logged_on' => '2027-05-04',
            'notes' => 'Design review',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Time logged');

    expect($task->refresh()->timeLogs)->toHaveCount(1)
        ->and($task->timeLogs->first()->minutes)->toBe(120)
        ->and($task->timeLogs->first()->user_id)->toBe($this->admin->id);
});

it('validates the log time modal', function () {
    $task = Task::factory()->create(['project_id' => $this->project->id]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->callTableAction('logTime', $task, ['minutes' => 0, 'logged_on' => '2027-05-04'])
        ->assertHasTableActionErrors(['minutes']);

    expect($task->refresh()->timeLogs)->toHaveCount(0);
});

it('bulk assigns tasks to a user', function () {
    $tasks = Task::factory()->count(2)->create(['project_id' => $this->project->id]);
    $newOwner = User::factory()->create();

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->callTableBulkAction('assign', $tasks, ['assignee_id' => $newOwner->id])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Tasks reassigned');

    expect(Task::query()->whereIn('id', $tasks->pluck('id'))->pluck('assignee_id')->unique()->all())
        ->toBe([$newOwner->id]);
});

it('bulk marks tasks done and stamps completed_at', function () {
    $tasks = Task::factory()->count(2)->create([
        'project_id' => $this->project->id,
        'status' => TaskStatus::Todo,
        'completed_at' => null,
    ]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->callTableBulkAction('markDone', $tasks)
        ->assertHasNoTableBulkActionErrors();

    $tasks->each(function (Task $task) {
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Done)
            ->and($task->completed_at)->not->toBeNull();
    });
});
