<?php

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->team = Team::factory()->create();
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

it('saves time logs through the repeater relationship', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['project_id' => $this->project->id]);

    Livewire::test(EditTask::class, ['record' => $task->getKey()])
        ->fillForm([
            'timeLogs' => [
                ['user_id' => $user->id, 'minutes' => 90, 'logged_on' => '2027-04-01', 'notes' => 'Pairing'],
                ['user_id' => $user->id, 'minutes' => 45, 'logged_on' => '2027-04-02', 'notes' => null],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($task->refresh()->timeLogs)->toHaveCount(2)
        ->and($task->timeLogs->sum('minutes'))->toBe(135);
});

it('requires minutes on every logged time row', function () {
    $task = Task::factory()->create(['project_id' => $this->project->id]);
    $user = User::factory()->create();

    Livewire::test(EditTask::class, ['record' => $task->getKey()])
        ->fillForm([
            'timeLogs' => [
                ['user_id' => $user->id, 'minutes' => null, 'logged_on' => '2027-04-01', 'notes' => null],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['timeLogs.0.minutes']);
});

it('attaches tags through the checkbox list', function () {
    $tags = Tag::factory()->count(2)->create(['team_id' => $this->team->id]);

    Livewire::test(CreateTask::class)
        ->fillForm([
            'project_id' => $this->project->id,
            'title' => 'Ship the landing page',
            'status' => TaskStatus::Todo->value,
            'priority' => Priority::High->value,
            'tags' => $tags->pluck('id')->all(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Task::query()->where('title', 'Ship the landing page')->firstOrFail()->tags)
        ->toHaveCount(2);
});
