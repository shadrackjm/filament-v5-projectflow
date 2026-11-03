<?php

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('rejects a client that belongs to a different team', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $theirs = Client::factory()->create(['team_id' => $teamB->id, 'is_active' => true]);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'team_id' => $teamA->id,
            'name' => 'Cross Team Project',
            'status' => ProjectStatus::Planning->value,
            'priority' => 'medium',
            'client_id' => $theirs->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['client_id']);
});
