<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Filament reads database notifications from this array shape, so build it
     * with Filament's own Notification class rather than by hand.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('You have a new task')
            ->body("{$this->assignedBy->name} assigned you “{$this->task->title}” on {$this->task->project->name}.")
            ->icon($this->task->priority->getIcon())
            ->iconColor($this->task->priority->getColor())
            ->actions([
                Action::make('view')
                    ->label('Open task')
                    ->button()
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task]), shouldOpenInNewTab: false)
                    ->markAsRead(),

                Action::make('dismiss')
                    ->label('Dismiss')
                    ->link()
                    ->close(),
            ])
            ->getDatabaseMessage();
    }
}
