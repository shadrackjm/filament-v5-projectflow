<?php
namespace App\Filament\Resources\Projects\Tables;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                SelectFilter::make('status')
                    ->options(ProjectStatus::class)
                    ->multiple()
                    ->preload(),

                SelectFilter::make('priority')
                    ->options(Priority::class)
                    ->multiple(),

                SelectFilter::make('team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_archived')
                    ->label('Archived')
                    ->placeholder('Not archived')
                    ->trueLabel('Archived only')
                    ->falseLabel('Not archived')
                    ->default(false),

                TrashedFilter::make(),

                Filter::make('overdue')
                    ->label('Overdue only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('due_at', '<', now())
                        ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value])),

                Filter::make('due_between')
                    ->schema([
                        DatePicker::make('due_from')->label('Due from')->native(false),
                        DatePicker::make('due_until')->label('Due until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['due_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_at', '>=', $date))
                        ->when($data['due_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['due_from'] ?? null) {
                            $indicators[] = 'Due from '.Carbon::parse($data['due_from'])->toFormattedDateString();
                        }

                        if ($data['due_until'] ?? null) {
                            $indicators[] = 'Due until '.Carbon::parse($data['due_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
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
