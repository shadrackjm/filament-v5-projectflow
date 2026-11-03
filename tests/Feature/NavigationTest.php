<?php

use App\Enums\ProjectStatus;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
    $this->team = Team::factory()->create();
});

it('badges the projects nav item with the active count', function () {
    Project::factory()->count(3)->create(['team_id' => $this->team->id, 'status' => ProjectStatus::Active]);
    Project::factory()->create(['team_id' => $this->team->id, 'status' => ProjectStatus::Planning]);

    expect(ProjectResource::getNavigationBadge())->toBe('3');
});

it('hides the projects badge when nothing is active', function () {
    Project::factory()->create(['team_id' => $this->team->id, 'status' => ProjectStatus::Planning]);

    expect(ProjectResource::getNavigationBadge())->toBeNull();
});

it('badges overdue tasks in red', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    Task::factory()->count(2)->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->addWeek(),
        'completed_at' => null,
    ]);

    expect(TaskResource::getNavigationBadge())->toBe('2')
        ->and(TaskResource::getNavigationBadgeColor())->toBe('danger');
});

it('finds a project through global search, including by client name', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Lighthouse Rebuild',
    ]);

    $results = ProjectResource::getGlobalSearchResults('Lighthouse');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Lighthouse Rebuild')
        ->and($results->first()->details)->toHaveKey('Team');
});

it('keeps the settings page away from non-administrators', function () {
    expect(Settings::canAccess())->toBeTrue();

    $this->actingAs(User::factory()->create(['is_admin' => false]));

    expect(Settings::canAccess())->toBeFalse();
});

it('saves and reloads studio settings', function () {
    Cache::forget(Settings::CACHE_KEY);

    Livewire::test(Settings::class)
        ->assertSchemaStateSet(['working_hours_per_day' => 7.5])
        ->fillForm([
            'default_priority' => 'high',
            'working_hours_per_day' => 6,
            'default_hourly_rate' => 150,
            'require_time_notes' => true,
            'overdue_warning_days' => 7,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Settings saved');

    // Numeric TextInputs come back as floats, hence toEqual rather than toBe.
    expect(Settings::current()['default_hourly_rate'])->toEqual(150)
        ->and(Settings::current()['require_time_notes'])->toBeTrue();
});

it('resets studio settings back to the defaults', function () {
    Cache::forever(Settings::CACHE_KEY, ['default_hourly_rate' => 999]);

    Livewire::test(Settings::class)
        ->callAction('reset')
        ->assertNotified('Settings reset');

    expect(Settings::current()['default_hourly_rate'])->toBe(120);
});
