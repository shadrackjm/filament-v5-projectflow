<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TasksByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Where the work is';

    protected ?string $description = 'Tasks by status, right now.';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Task::query()
            ->when($this->pageFilters['team_id'] ?? null, fn (Builder $query, $teamId) => $query->whereHas('project', fn (Builder $q) => $q->where('team_id', $teamId)))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(TaskStatus::cases());

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => $statuses->map(fn (TaskStatus $status): int => (int) ($counts[$status->value] ?? 0))->all(),
                    'backgroundColor' => [
                        '#9ca3af', // to do — gray
                        '#f59e0b', // in progress — amber
                        '#3b82f6', // in review — blue
                        '#10b981', // done — emerald
                    ],
                ],
            ],
            'labels' => $statuses->map(fn (TaskStatus $status): string => $status->getLabel())->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
