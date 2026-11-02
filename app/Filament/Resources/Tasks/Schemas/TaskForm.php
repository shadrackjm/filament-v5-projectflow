<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Task')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->columns(2)
                            ->schema([
                                Select::make('project_id')
                                    ->relationship('project', 'name')
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('assignee_id')
                                    ->label('Assigned to')
                                    ->relationship('assignee', 'name')
                                    ->native(false)
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                CheckboxList::make('tags')
                                    ->relationship('tags', 'name')
                                    ->columns(3)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Scheduling')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->columns(2)
                            ->schema([
                                Select::make('status')
                                    ->options(TaskStatus::class)
                                    ->default(TaskStatus::Todo)
                                    ->native(false)
                                    ->required()
                                    ->live(),

                                Select::make('priority')
                                    ->options(Priority::class)
                                    ->default(Priority::Medium)
                                    ->native(false)
                                    ->required(),

                                DatePicker::make('due_at')
                                    ->label('Due date')
                                    ->native(false)
                                    ->displayFormat('d M Y'),

                                TextInput::make('estimated_hours')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('hrs'),

                                DateTimePicker::make('completed_at')
                                    ->native(false)
                                    ->visible(fn (Get $get): bool => $get('status') === TaskStatus::Done),

                                Toggle::make('is_billable')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Tabs\Tab::make('Time logged')
                            ->icon(Heroicon::OutlinedClock)
                        ->badge(fn (?Task $record): ?int => $record?->timeLogs()->count() ?: null)
                            ->schema([
                                Repeater::make('timeLogs')
                                    ->relationship()
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->addActionLabel('Log some time')
                                    ->collapsible()
                                    ->collapsed()
                                    ->cloneable()
                                    ->itemLabel(fn (array $state): ?string => filled($state['minutes'] ?? null)
                                        ? round(((int) $state['minutes']) / 60, 2).' hrs on '.($state['logged_on'] ?? '?')
                                        : null)
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Who')
                                            ->relationship('user', 'name')
                                            ->native(false)
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        TextInput::make('minutes')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('min')
                                            ->required(),

                                        DatePicker::make('logged_on')
                                            ->native(false)
                                            ->default(today())
                                            ->required(),

                                        TextInput::make('notes')
                                            ->maxLength(255),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
