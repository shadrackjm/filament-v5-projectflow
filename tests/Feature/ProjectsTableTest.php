<?php

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
