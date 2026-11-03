<?php

namespace App\Filament\App\Resources\Tasks\Schemas;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                // Users aren't tenanted: list this team's members, not every user in the database.
                Select::make('assignee_id')
                    ->label('Assigned to')
                    ->options(fn (): array => Filament::getTenant()
                        ->members()
                        ->orderBy('name')
                        ->pluck('name', 'users.id')
                        ->all())
                    ->searchable(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(TaskStatus::class)
                    ->default('todo')
                    ->required(),
                Select::make('priority')
                    ->options(Priority::class)
                    ->default('medium')
                    ->required(),
                TextInput::make('estimated_hours')
                    ->numeric(),
                DatePicker::make('due_at'),
                Toggle::make('is_billable')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
