<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Project details')
                            ->description('The name and pitch everyone on the team will see.')
                            ->icon(Heroicon::OutlinedRectangleStack)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Planning')
                            ->description('Dates and money. Everything here is optional while a project is still an idea.')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->columns(2)
                            ->schema([
                                DatePicker::make('starts_at')
                                    ->label('Start date')
                                    ->native(false),
                                DatePicker::make('due_at')
                                    ->label('Due date')
                                    ->native(false)
                                    ->afterOrEqual('starts_at'),
                                TextInput::make('budget')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Ownership')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Select::make('team_id')
                                    ->relationship('team', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('lead_id')
                                    ->label('Project lead')
                                    ->relationship('lead', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Section::make('Status')
                            ->icon(Heroicon::OutlinedFlag)
                            ->schema([
                                Select::make('status')
                                    ->options(ProjectStatus::class)
                                    ->default(ProjectStatus::Planning)
                                    ->required(),
                                Select::make('priority')
                                    ->options(Priority::class)
                                    ->default(Priority::Medium)
                                    ->required(),
                                ColorPicker::make('color')
                                    ->default('#3b82f6'),
                            ]),

                        Section::make('Housekeeping')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Toggle::make('is_archived')
                                    ->label('Archived')
                                    ->helperText('Archived projects stay searchable but drop out of the default list.'),
                            ]),
                    ]),
            ]);
    }
}
