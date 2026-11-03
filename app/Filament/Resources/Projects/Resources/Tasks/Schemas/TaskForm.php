<?php

namespace App\Filament\Resources\Projects\Resources\Tasks\Schemas;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // No project_id select — the URL already says which project we are in.
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),

                Select::make('assignee_id')
                    ->label('Assigned to')
                    ->relationship('assignee', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options(TaskStatus::class)
                    ->default(TaskStatus::Todo)
                    ->native(false)
                    ->required(),

                Select::make('priority')
                    ->options(Priority::class)
                    ->default(Priority::Medium)
                    ->native(false)
                    ->required(),

                DatePicker::make('due_at')
                    ->label('Due date')
                    ->native(false),
            ]);
    }
}
