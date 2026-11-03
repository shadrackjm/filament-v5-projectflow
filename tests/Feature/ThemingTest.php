<?php

use App\Filament\Plugins\StudioBrandingPlugin;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->team = Team::factory()->create();
});

it('registers the branding plugin on the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->hasPlugin('studio-branding'))->toBeTrue()
        ->and($panel->getPlugin('studio-branding'))->toBeInstanceOf(StudioBrandingPlugin::class)
        ->and($panel->getBrandName())->toBe('ProjectFlow');
});

it('injects the environment banner from a render hook', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('nothing here is real data');
});

it('injects the plugin footer on every page', function () {
    $this->get('/admin/projects')
        ->assertOk()
        ->assertSee('ProjectFlow — ©', escape: false)
        ->assertSee('Get help');
});

it('shows the overdue warning hook on the dashboard only when work is late', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    Task::factory()->count(2)->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
    ]);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('2 tasks overdue');
});

it('hides the overdue warning when nothing is late', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    Task::factory()->create([
        'project_id' => $project->id,
        'due_at' => now()->addWeek(),
        'completed_at' => null,
    ]);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('tasks overdue');
});

it('scopes the overdue hook to the dashboard', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    // Two, not one: with a single task the card says "1 task overdue", and
    // assertDontSee('tasks overdue') would pass whether the hook leaked or not.
    Task::factory()->count(2)->create([
        'project_id' => $project->id,
        'due_at' => now()->subWeek(),
        'completed_at' => null,
    ]);

    $this->get('/admin/clients')
        ->assertOk()
        ->assertDontSee('tasks overdue');
});

it('serves the compiled custom theme stylesheet', function () {
    // viteTheme() renders the built asset from the manifest, so run `npm run build`
    // before this test — it is checking that the theme is actually wired up.
    $this->get('/admin')
        ->assertOk()
        ->assertSee('/build/assets/theme-', escape: false);
});
