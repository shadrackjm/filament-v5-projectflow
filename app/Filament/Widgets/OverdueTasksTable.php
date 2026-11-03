<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OverdueTasksTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Overdue tasks')
            ->description('Past their due date and still not finished.')
            ->query(fn (): Builder => Task::query()
                ->with(['project', 'assignee'])
                ->when($this->pageFilters['team_id'] ?? null, fn (Builder $query, $teamId) => $query->whereHas('project', fn (Builder $q) => $q->where('team_id', $teamId)))
                ->whereDate('due_at', '<', now())
                ->whereNull('completed_at')
                ->orderBy('due_at'))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->emptyStateHeading('Nothing is overdue')
            ->emptyStateDescription('Genuinely impressive. Go and enjoy your afternoon.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Task $record): string => $record->project->name)
                    ->url(fn (Task $record): string => TaskResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),

                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->description(fn (Task $record): ?string => $record->due_at?->diffForHumans())
                    ->color('danger')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('complete')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Mark done')
                    ->action(function (Task $record): void {
                        $record->update([
                            'status' => TaskStatus::Done,
                            'completed_at' => now(),
                        ]);

                        Notification::make()->title('Task completed')->success()->send();
                    }),
            ]);
    }
}
