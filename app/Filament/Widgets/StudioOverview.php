<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StudioOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $overdue = $this->overdueTaskCount();

        return [
            Stat::make('Active projects', $this->projects()->where('status', ProjectStatus::Active)->count())
                ->description($this->projects()->where('status', ProjectStatus::Planning)->count().' still in planning')
                ->descriptionIcon(Heroicon::OutlinedLightBulb)
                ->color('success')
                ->chart($this->projectsStartedPerWeek()),

            Stat::make('Overdue tasks', $overdue)
                ->description($overdue > 0 ? 'Needs attention today' : 'Nothing is late')
                ->descriptionIcon($overdue > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make('Hours logged', $this->hoursLogged())
                ->description('Between the dashboard dates')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('info')
                ->chart($this->hoursPerWeek()),
        ];
    }

    /**
     * Every project query in this widget starts here, so the dashboard
     * team filter only has to be applied in one place.
     */
    protected function projects(): Builder
    {
        return Project::query()
            ->when($this->pageFilters['team_id'] ?? null, fn (Builder $query, $teamId) => $query->where('team_id', $teamId));
    }

    protected function timeLogs(): Builder
    {
        return TimeLog::query()
            ->when(
                $this->pageFilters['team_id'] ?? null,
                fn (Builder $query, $teamId) => $query->whereHas('task.project', fn (Builder $q) => $q->where('team_id', $teamId))
            );
    }

    protected function overdueTaskCount(): int
    {
        return Task::query()
            ->whereIn('project_id', $this->projects()->select('id'))
            ->whereDate('due_at', '<', now())
            ->whereNull('completed_at')
            ->count();
    }

    protected function hoursLogged(): string
    {
        $from = Carbon::parse($this->pageFilters['from'] ?? now()->startOfMonth())->startOfDay();
        $until = Carbon::parse($this->pageFilters['until'] ?? now()->endOfMonth())->endOfDay();

        $minutes = $this->timeLogs()
            ->whereBetween('logged_on', [$from, $until])
            ->sum('minutes');

        return round($minutes / 60, 1).' hrs';
    }

    /**
     * @return array<int, int>
     */
    protected function projectsStartedPerWeek(): array
    {
        return collect(range(7, 0))
            ->map(fn (int $weeksAgo): int => $this->projects()
                ->whereBetween('created_at', [
                    now()->subWeeks($weeksAgo)->startOfWeek(),
                    now()->subWeeks($weeksAgo)->endOfWeek(),
                ])
                ->count())
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function hoursPerWeek(): array
    {
        return collect(range(7, 0))
            ->map(fn (int $weeksAgo): int => (int) round($this->timeLogs()
                ->whereBetween('logged_on', [
                    now()->subWeeks($weeksAgo)->startOfWeek(),
                    now()->subWeeks($weeksAgo)->endOfWeek(),
                ])
                ->sum('minutes') / 60))
            ->all();
    }
}
