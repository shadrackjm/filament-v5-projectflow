<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
                Select::make('client_id')
                    ->relationship('client', 'name'),
                Select::make('lead_id')
                    ->relationship('lead', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ProjectStatus::class)
                    ->default('planning')
                    ->required(),
                Select::make('priority')
                    ->options(Priority::class)
                    ->default('medium')
                    ->required(),
                TextInput::make('color')
                    ->required()
                    ->default('#3b82f6'),
                TextInput::make('budget')
                    ->numeric(),
                DatePicker::make('starts_at'),
                DatePicker::make('due_at'),
                Toggle::make('is_archived')
                    ->required(),
            ]);
    }
}
