<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\TaskStatus;
use App\Filament\Resources\Projects\Resources\Tasks\TaskResource as NestedTaskResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = NestedTaskResource::class;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->tasks()->whereNull('completed_at')->count() ?: null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('due_at')
            ->heading('Tasks')
            ->description('Everything that has to happen for this project to ship.')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),

                SelectColumn::make('status')
                    ->options(TaskStatus::class)
                    ->selectablePlaceholder(false),

                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned')
                    ->searchable(),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TaskStatus::class)
                    ->multiple(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add task'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('assignee'))
            ->emptyStateHeading('No tasks yet')
            ->emptyStateDescription('Break the project down into the work that actually has to happen.');
    }
}
