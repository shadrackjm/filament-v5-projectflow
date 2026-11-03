<?php

use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
    $this->project = Project::factory()->create(['team_id' => Team::factory()]);
});

it('renders every task column', function () {
    $tasks = Task::factory()->count(3)->create(['project_id' => $this->project->id]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->assertCanSeeTableRecords($tasks)
        ->assertCanRenderTableColumn('title')
        ->assertCanRenderTableColumn('status')
        ->assertCanRenderTableColumn('estimated_hours')
        ->assertCanRenderTableColumn('logged_hours')
        ->assertCanRenderTableColumn('is_billable')
        ->assertCanRenderTableColumn('due_at');
});

it('updates the status inline from the select column', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'status' => TaskStatus::Todo,
    ]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$task])
        ->assertTableColumnStateSet('status', TaskStatus::Todo, record: $task)
        ->call('updateTableColumnState', 'status', (string) $task->getKey(), TaskStatus::InProgress->value);

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress);
});

it('toggles is_billable inline', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'is_billable' => true,
    ]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->call('updateTableColumnState', 'is_billable', (string) $task->getKey(), false);

    expect($task->refresh()->is_billable)->toBeFalse();
});

it('filters down to tasks assigned to the signed-in user', function () {
    $mine = Task::factory()->create([
        'project_id' => $this->project->id,
        'assignee_id' => $this->admin->id,
    ]);

    $theirs = Task::factory()->create([
        'project_id' => $this->project->id,
        'assignee_id' => User::factory()->create()->id,
    ]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->filterTable('mine')
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('groups tasks by project', function () {
    Task::factory()->count(2)->create(['project_id' => $this->project->id]);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->set('tableGrouping', 'project.name')
        ->assertSee($this->project->name);
});
