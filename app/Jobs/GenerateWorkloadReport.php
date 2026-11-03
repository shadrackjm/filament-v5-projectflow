<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateWorkloadReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Team $team,
        public User $requestedBy,
    ) {}

    public function handle(): void
    {
        $open = Task::query()
            ->whereHas('project', fn ($query) => $query->where('team_id', $this->team->id))
            ->whereNull('completed_at')
            ->count();

        $unassigned = Task::query()
            ->whereHas('project', fn ($query) => $query->where('team_id', $this->team->id))
            ->whereNull('completed_at')
            ->whereNull('assignee_id')
            ->count();

        Notification::make()
            ->title("Workload report for {$this->team->name}")
            ->body("{$open} open tasks, {$unassigned} of them unassigned.")
            ->icon('heroicon-o-document-chart-bar')
            ->color($unassigned > 0 ? 'warning' : 'success')
            ->sendToDatabase($this->requestedBy, isEventDispatched: true);
    }
}
