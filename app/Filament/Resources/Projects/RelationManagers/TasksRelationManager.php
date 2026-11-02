<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->tasks()->whereNull('completed_at')->count() ?: null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('assignee_id')
                    ->label('Assigned to')
                    ->relationship('assignee', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options(TaskStatus::class)
                    ->default(TaskStatus::Todo)
                    ->native(false)
                    ->required(),

                Select::make('priority')
                    ->options(Priority::class)
                    ->default(Priority::Medium)
                    ->native(false)
                    ->required(),

                DatePicker::make('due_at')
                    ->label('Due date')
                    ->native(false),
            ]);
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
