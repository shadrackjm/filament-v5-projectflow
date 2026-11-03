<?php

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tenancy tests go over HTTP on purpose
|--------------------------------------------------------------------------
| Filament registers the tenancy global scope while *booting the panel*, and the
| panel boots as part of a real request. A bare Livewire component test skips that
| middleware, so scoping would silently not apply and the test would prove nothing.
| Hitting the tenant URL exercises the whole stack — which is the thing we care
| about with tenancy, because a leak here is a data breach, not a cosmetic bug.
*/

beforeEach(function () {
    $this->ourTeam = Team::factory()->create(['name' => 'Northwind Studio', 'slug' => 'northwind-studio']);
    $this->theirTeam = Team::factory()->create(['name' => 'Blue Harbour', 'slug' => 'blue-harbour']);

    $this->user = User::factory()->create(['is_admin' => false]);
    $this->ourTeam->members()->attach($this->user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($this->user);
});

it('puts the tenant slug in the URL', function () {
    $this->get('/app/northwind-studio')->assertOk();
});

/*
 * Filament answers 404 rather than 403 for a tenant you may not access, so the
 * response never confirms whether that tenant exists at all.
 */
it('refuses a tenant the user does not belong to', function () {
    $this->get('/app/blue-harbour')->assertNotFound();
});

it('shows only the current team\'s projects', function () {
    Project::factory()->create([
        'team_id' => $this->ourTeam->id,
        'name' => 'Lighthouse Rebuild',
        'is_archived' => false,
    ]);

    Project::factory()->create([
        'team_id' => $this->theirTeam->id,
        'name' => 'Someone Elses Secret',
        'is_archived' => false,
    ]);

    $this->get('/app/northwind-studio/projects')
        ->assertOk()
        ->assertSee('Lighthouse Rebuild')
        ->assertDontSee('Someone Elses Secret');
});

it('will not open another team\'s project even by guessing the id', function () {
    $theirs = Project::factory()->create(['team_id' => $this->theirTeam->id]);

    $this->get("/app/northwind-studio/projects/{$theirs->id}/edit")
        ->assertNotFound();
});

it('scopes tasks through their project even though tasks have no team_id', function () {
    $ourProject = Project::factory()->create(['team_id' => $this->ourTeam->id]);
    $theirProject = Project::factory()->create(['team_id' => $this->theirTeam->id]);

    Task::factory()->create(['project_id' => $ourProject->id, 'title' => 'Our own task']);
    Task::factory()->create(['project_id' => $theirProject->id, 'title' => 'Their private task']);

    $this->get('/app/northwind-studio/tasks')
        ->assertOk()
        ->assertSee('Our own task')
        ->assertDontSee('Their private task');
});

it('lists every team the user belongs to in the tenant menu', function () {
    $second = Team::factory()->create(['name' => 'Second Studio', 'slug' => 'second-studio']);
    $second->members()->attach($this->user, ['role' => TeamRole::Member->value]);

    $this->get('/app/northwind-studio')
        ->assertOk()
        ->assertSee('Northwind Studio')
        ->assertSee('Second Studio')
        ->assertDontSee('Blue Harbour');
});

it('renders the tenant registration page', function () {
    $this->get('/app/new')->assertOk();
});

it('renders the tenant profile page', function () {
    $this->get('/app/northwind-studio/profile')->assertOk();
});

it('reports the tenants a user may access', function () {
    expect($this->user->canAccessTenant($this->ourTeam))->toBeTrue()
        ->and($this->user->canAccessTenant($this->theirTeam))->toBeFalse();
});
