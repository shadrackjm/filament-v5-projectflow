<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_at')
            ->striped()
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width('1%'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Project $record): string => $record->client?->name ?? 'No client')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('lead.name')
                    ->label('Lead')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned')
                    ->toggleable(),

                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('progress')
                    ->label('Done')
                    ->state(function (Project $record): string {
                        $total = $record->tasks()->count();

                        if ($total === 0) {
                            return '—';
                        }

                        $done = $record->tasks()->whereNotNull('completed_at')->count();

                        return round($done / $total * 100).'%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '—' => 'gray',
                        (int) $state >= 80 => 'success',
                        (int) $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),

                TextColumn::make('budget')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd()
                    ->summarize(Sum::make()->money('USD')->label('Total'))
                    ->toggleable(),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (Project $record): string => match (true) {
                        blank($record->due_at) => 'gray',
                        $record->due_at->isPast() && $record->status !== ProjectStatus::Completed => 'danger',
                        $record->due_at->diffInDays(now()) <= 14 => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (Project $record): ?string => $record->due_at?->diffForHumans()),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
