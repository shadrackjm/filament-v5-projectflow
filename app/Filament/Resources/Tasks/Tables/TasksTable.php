<?php
namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
                    ->options(TaskStatus::class)
                    ->selectablePlaceholder(false)
                    ->rules(['required'])
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextInputColumn::make('estimated_hours')
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
