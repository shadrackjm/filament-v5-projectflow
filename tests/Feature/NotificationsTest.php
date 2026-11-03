<?php

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Teams\Pages\ListTeams;
use App\Jobs\GenerateWorkloadReport;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Ada Lovelace']);
    $this->actingAs($this->admin);
    $this->team = Team::factory()->create(['name' => 'Northwind Studio']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

it('notifies the new assignee when tasks are reassigned in bulk', function () {
    Notification::fake();

    $tasks = Task::factory()->count(2)->create(['project_id' => $this->project->id]);
    $newOwner = User::factory()->create();

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->callTableBulkAction('assign', $tasks, ['assignee_id' => $newOwner->id])
        ->assertHasNoTableBulkActionErrors();

    Notification::assertSentTimes(TaskAssigned::class, 2);
    Notification::assertSentTo($newOwner, TaskAssigned::class);
    Notification::assertNotSentTo($this->admin, TaskAssigned::class);
});

it('writes a database notification the bell icon can render', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'title' => 'Ship the pricing page',
    ]);

    $assignee = User::factory()->create();
    $assignee->notify(new TaskAssigned($task->load('project'), $this->admin));

    $row = $assignee->notifications()->sole();

    expect($row->data['title'])->toBe('You have a new task')
        ->and($row->data['body'])->toContain('Ada Lovelace')
        ->and($row->data['body'])->toContain('Ship the pricing page')
        ->and($row->data['actions'])->toHaveCount(2)
        ->and($row->read_at)->toBeNull();
});

it('queues the workload report rather than blocking the request', function () {
    Bus::fake();

    Livewire::test(ListTeams::class)
        ->callTableAction('workloadReport', $this->team)
        ->assertHasNoTableActionErrors()
        ->assertNotified('Report queued');

    Bus::assertDispatched(GenerateWorkloadReport::class);
});

it('sends the finished report to the person who asked for it', function () {
    Task::factory()->count(3)->create([
        'project_id' => $this->project->id,
        'completed_at' => null,
        'assignee_id' => null,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'completed_at' => null,
        'assignee_id' => $this->admin->id,
    ]);

    (new GenerateWorkloadReport($this->team, $this->admin))->handle();

    $row = $this->admin->notifications()->sole();

    expect($row->data['title'])->toBe('Workload report for Northwind Studio')
        ->and($row->data['body'])->toBe('4 open tasks, 3 of them unassigned.');
});
