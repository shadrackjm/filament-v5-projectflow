<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Project;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('description')
                                    ->hiddenLabel()
                                    ->html()
                                    ->prose()
                                    ->placeholder('No brief has been written for this project yet.')
                                    ->columnSpanFull(),
                            ]),

                        Tabs::make()
                            ->persistTabInQueryString()
                            ->columnSpanFull()
                            ->tabs([
                                Tabs\Tab::make('Overview')
                                    ->icon(Heroicon::OutlinedChartBar)
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('tasks_count')
                                                ->label('Tasks')
                                                ->state(fn (Project $record): int => $record->tasks()->count())
                                                ->badge()
                                                ->color('gray'),

                                            TextEntry::make('completed')
                                                ->label('Completed')
                                                ->state(fn (Project $record): string => $record->tasks()
                                                    ->whereNotNull('completed_at')->count()
                                                    .' of '.$record->tasks()->count())
                                                ->badge()
                                                ->color('success'),

                                            TextEntry::make('logged')
                                                ->label('Time logged')
                                                ->state(fn (Project $record): string => round(
                                                    $record->timeLogs()->sum('minutes') / 60, 1
                                                ).' hrs')
                                                ->badge()
                                                ->color('info'),
                                        ]),
                                    ]),

                                Tabs\Tab::make('Open tasks')
                                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                                    ->schema([
                                        RepeatableEntry::make('openTasks')
                                            ->hiddenLabel()
                                            ->state(fn (Project $record) => $record->tasks()
                                                ->whereNull('completed_at')
                                                ->with('assignee')
                                                ->orderBy('due_at')
                                                ->limit(10)
                                                ->get())
                                            ->columns(3)
                                            ->placeholder('Everything is done. Enjoy it while it lasts.')
                                            ->schema([
                                                TextEntry::make('title')
                                                    ->hiddenLabel()
                                                    ->weight(FontWeight::Medium),

                                                TextEntry::make('assignee.name')
                                                    ->hiddenLabel()
                                                    ->placeholder('Unassigned')
                                                    ->icon(Heroicon::OutlinedUser),

                                                TextEntry::make('due_at')
                                                    ->hiddenLabel()
                                                    ->date('d M Y')
                                                    ->placeholder('No due date')
                                                    ->icon(Heroicon::OutlinedCalendar),
                                            ]),
                                    ]),

                                Tabs\Tab::make('Audit')
                                    ->icon(Heroicon::OutlinedClock)
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Created')
                                            ->dateTime('d M Y, H:i')
                                            ->since()
                                            ->dateTooltip('d M Y, H:i'),

                                        TextEntry::make('updated_at')
                                            ->label('Last updated')
                                            ->dateTime('d M Y, H:i')
                                            ->since()
                                            ->dateTooltip('d M Y, H:i'),
                                    ]),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')->badge(),
                                TextEntry::make('priority')->badge(),
                                ColorEntry::make('color')->label('Colour'),
                            ]),

                        Section::make('Ownership')
                            ->schema([
                                TextEntry::make('team.name')
                                    ->label('Team')
                                    ->icon(Heroicon::OutlinedUsers),

                                TextEntry::make('client.name')
                                    ->label('Client')
                                    ->placeholder('No client')
                                    ->icon(Heroicon::OutlinedBriefcase)
                                    ->url(fn (Project $record): ?string => $record->client
                                        ? ClientResource::getUrl('view', ['record' => $record->client])
                                        : null),

                                TextEntry::make('lead.name')
                                    ->label('Project lead')
                                    ->placeholder('Unassigned')
                                    ->icon(Heroicon::OutlinedUser),
                            ]),

                        Section::make('Money & dates')
                            ->schema([
                                TextEntry::make('budget')
                                    ->money('USD')
                                    ->placeholder('No budget set'),

                                TextEntry::make('starts_at')
                                    ->label('Starts')
                                    ->date('d M Y')
                                    ->placeholder('Not scheduled'),

                                TextEntry::make('due_at')
                                    ->label('Due')
                                    ->date('d M Y')
                                    ->placeholder('No deadline')
                                    ->badge()
                                    ->color(fn (Project $record): string => $record->due_at?->isPast()
                                        && $record->status !== ProjectStatus::Completed
                                            ? 'danger'
                                            : 'gray'),

                                Text::make('Overdue projects still bill. Update the deadline or close the project.')
                                    ->color('danger')
                                    ->size('sm')
                                    ->visible(fn (Project $record): bool => (bool) $record->due_at?->isPast()
                                        && $record->status !== ProjectStatus::Completed),
                            ]),
                    ]),
            ]);
    }
}
