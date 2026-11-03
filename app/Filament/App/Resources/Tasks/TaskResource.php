<?php

namespace App\Filament\App\Resources\Tasks;

use App\Filament\App\Resources\Tasks\Pages\CreateTask;
use App\Filament\App\Resources\Tasks\Pages\EditTask;
use App\Filament\App\Resources\Tasks\Pages\ListTasks;
use App\Filament\App\Resources\Tasks\Schemas\TaskForm;
use App\Filament\App\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * A Task has no team_id — it reaches the tenant through its project. Filament
     * cannot guess that, so we turn the automatic scoping off and do it by hand.
     */
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->with(['project', 'assignee'])
            ->when(
                $tenant,
                fn (Builder $query) => $query->whereHas(
                    'project',
                    fn (Builder $projectQuery) => $projectQuery->where('projects.team_id', $tenant->getKey())
                )
            );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
