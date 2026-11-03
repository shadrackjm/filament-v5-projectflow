<?php
namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_at')
            ->deferLoading()
            ->persistFiltersInSession()
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->groups([
                Group::make('project.name')->label('Project')->collapsible(),
                Group::make('status')->collapsible(),
                Group::make('assignee.name')->label('Assignee')->collapsible(),
            ])
            ->columns([
                ImageColumn::make('assignee.avatar_path')
                    ->label('')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(fn (Task $record): string => 'https://ui-avatars.com/api/?name='
                        .urlencode($record->assignee?->name ?? '?').'&color=FFFFFF&background=6366f1'),

                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Task $record): ?string => $record->project?->name),

                SelectColumn::make('status')
                    ->disabled(fn (Task $record): bool => auth()->user()->cannot('update', $record))
                    ->options(TaskStatus::class)
                    ->selectablePlaceholder(false)
                    ->rules(['required'])
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextInputColumn::make('estimated_hours')
                    ->disabled(fn (Task $record): bool => auth()->user()->cannot('update', $record))
                    ->label('Est.')
                    ->type('number')
                    ->rules(['numeric', 'min:0'])
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total est.'))
                    ->toggleable(),

                TextColumn::make('logged_hours')
                    ->label('Logged')
                    ->state(fn (Task $record): string => round($record->timeLogs()->sum('minutes') / 60, 1).'h')
                    ->alignEnd()
                    ->toggleable(),

                ToggleColumn::make('is_billable')
                    ->disabled(fn (Task $record): bool => auth()->user()->cannot('update', $record))
                    ->label('Billable')
                    ->toggleable(),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (Task $record): string => $record->due_at?->isPast() && $record->status !== TaskStatus::Done
                        ? 'danger'
                        : 'gray'),

                IconColumn::make('completed_at')
                    ->label('Done')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TaskStatus::class)
                    ->multiple(),

                SelectFilter::make('priority')
                    ->options(Priority::class)
                    ->multiple(),

                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_billable')
                    ->label('Billable'),

                Filter::make('mine')
                    ->label('Assigned to me')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('assignee_id', auth()->id())),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('logTime')
                    ->label('Log time')
                    ->icon(Heroicon::OutlinedClock)
                    ->iconButton()
                    ->modalWidth(Width::Medium)
                    ->schema([
                        TextInput::make('minutes')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('min')
                            ->required()
                            ->default(30),
                        DatePicker::make('logged_on')
                            ->native(false)
                            ->default(today())
                            ->required(),
                        TextInput::make('notes')
                            ->maxLength(255),
                    ])
                    ->action(function (Task $record, array $data): void {
                        $record->timeLogs()->create([
                            'user_id' => auth()->id(),
                            'minutes' => $data['minutes'],
                            'logged_on' => $data['logged_on'],
                            'notes' => $data['notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Time logged')
                            ->body(round($data['minutes'] / 60, 2).' hours added to '.$record->title)
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('assign')
                    ->label('Assign to…')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->schema([
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->native(false)
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $assignee = User::findOrFail($data['assignee_id']);

                        $records->each->update(['assignee_id' => $assignee->id]);

                        // Tell the person who now owns the work, not just the person who moved it.
                        $records->each(fn (Task $task) => $assignee->notify(
                            new TaskAssigned($task->load('project'), auth()->user())
                        ));

                        Notification::make()
                            ->title('Tasks reassigned')
                            ->body($records->count().' tasks are now with '.$assignee->name.'.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('markDone')
                    ->label('Mark done')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each->update([
                            'status' => TaskStatus::Done,
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title($records->count().' tasks completed')
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
