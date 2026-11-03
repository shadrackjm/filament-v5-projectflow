<?php
namespace App\Filament\Resources\Projects\Tables;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Filament\Exports\ProjectExporter;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('progress')
                    ->label('Done')
                    ->state(function (Project $record): string {
                        // Both counts come from the resource's withCount() — no per-row queries.
                        $total = (int) ($record->tasks_count ?? 0);

                        if ($total === 0) {
                            return '—';
                        }

                        return round(((int) ($record->completed_tasks_count ?? 0)) / $total * 100).'%';
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProjectExporter::class)
                    ->label('Export CSV'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('complete')
                        ->authorize('update')
                        ->authorizationTooltip()
                        ->label('Mark completed')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark this project completed?')
                        ->modalDescription('Any tasks still open will stay open — this only changes the project status.')
                        ->modalSubmitActionLabel('Yes, complete it')
                        ->visible(fn (Project $record): bool => $record->status !== ProjectStatus::Completed)
                        ->action(function (Project $record): void {
                            $record->update(['status' => ProjectStatus::Completed]);

                            Notification::make()
                                ->title('Project completed')
                                ->body("{$record->name} is done. Nice work.")
                                ->success()
                                ->send();
                        }),

                    Action::make('archive')
                        ->authorize('update')
                        ->authorizationTooltip()
                        ->label(fn (Project $record): string => $record->is_archived ? 'Unarchive' : 'Archive')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('gray')
                        ->action(fn (Project $record) => $record->update(['is_archived' => ! $record->is_archived])),

                    ReplicateAction::make()
                        ->excludeAttributes(['slug', 'tasks_count', 'completed_tasks_count'])
                        ->beforeReplicaSaved(function (Project $replica, Project $record): void {
                            $replica->name = $record->name.' (copy)';
                            $replica->slug = $record->slug.'-copy-'.Str::random(4);
                            $replica->status = ProjectStatus::Planning;
                        })
                        ->successNotificationTitle('Project duplicated'),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('setStatus')
                    ->label('Change status')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->schema([
                        Select::make('status')
                            ->options(ProjectStatus::class)
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status updated')
                            ->body($records->count().' projects moved to '.$data['status']->getLabel().'.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
