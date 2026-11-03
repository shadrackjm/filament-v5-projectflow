<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Enums\TeamRole;
use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Start a new team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Team name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(Team::class, 'slug')
                ->helperText('Appears in every URL for this team.'),
        ]);
    }

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);

        // Whoever creates the team owns it.
        $team->members()->attach(auth()->user(), ['role' => TeamRole::Owner->value]);

        return $team;
    }
}
