<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Models\Client;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

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
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                        if ($operation !== 'create') {
                                            return;
                                        }

                                        $set('slug', Str::slug($state ?? ''));
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->alphaDash()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Used in URLs. Generated from the name, but you can override it.'),
                                RichEditor::make('description')
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'strike', 'link'],
                                        ['h2', 'h3', 'bulletList', 'orderedList'],
                                        ['undo', 'redo'],
                                    ])
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
                                    ->displayFormat('d M Y')
                                    ->afterOrEqual('starts_at')
                                    ->live()
                                    ->helperText(function (Get $get): ?string {
                                        $start = $get('starts_at');
                                        $due = $get('due_at');

                                        if (blank($start) || blank($due)) {
                                            return null;
                                        }

                                        return 'That is '.CarbonImmutable::parse($start)
                                            ->diffInDays(CarbonImmutable::parse($due)).' days of runway.';
                                    }),
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
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('client_id', null);
                                        $set('lead_id', null);
                                    }),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->native(false)
                                    ->searchable()
                                    ->options(fn (Get $get): array => Client::query()
                                        ->where('team_id', $get('team_id'))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->disabled(fn (Get $get): bool => blank($get('team_id')))
                                    ->helperText(fn (Get $get): ?string => blank($get('team_id'))
                                        ? 'Pick a team first.'
                                        : null),
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
