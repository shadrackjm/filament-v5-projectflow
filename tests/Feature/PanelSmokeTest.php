<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders the login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('renders the password reset and profile pages', function () {
    $this->get('/admin/password-reset/request')->assertOk();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get('/admin/profile')
        ->assertOk();
});

it('lets an admin reach the dashboard and every resource index', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get('/admin')->assertOk();

    collect([
        '/admin/projects',
        '/admin/clients',
        '/admin/tasks',
        '/admin/settings',
        // clustered resources live under the cluster's path
        '/admin/administration/users',
        '/admin/administration/tags',
        '/admin/administration/teams',
    ])->each(fn (string $url) => $this->get($url)->assertOk());
});

it('renders every create, view and edit page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $team = Team::factory()->create();
    $client = Client::factory()->create(['team_id' => $team->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'client_id' => $client->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    collect([
        '/admin/projects/create',
        "/admin/projects/{$project->id}",
        "/admin/projects/{$project->id}/edit",
        '/admin/clients/create',
        "/admin/clients/{$client->id}",
        "/admin/clients/{$client->id}/edit",
        '/admin/tasks/create',
        "/admin/tasks/{$task->id}",
        "/admin/tasks/{$task->id}/edit",
        '/admin/administration/teams/create',
        "/admin/administration/teams/{$team->id}/edit",
    ])->each(fn (string $url) => $this->get($url)->assertOk());
});

it('redirects the cluster root to its first page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get('/admin/administration')
        ->assertRedirect('/admin/administration/teams');
});

it('renders the nested task create page under a project', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $project = Project::factory()->create(['team_id' => Team::factory()]);

    $this->get("/admin/projects/{$project->id}/tasks/create")->assertOk();
});

it('renders the nested task edit page under a project', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $project = Project::factory()->create(['team_id' => Team::factory()]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $this->get("/admin/projects/{$project->id}/tasks/{$task->id}/edit")->assertOk();
});
