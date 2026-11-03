@php
    $overdue = \App\Models\Task::query()
        ->whereDate('due_at', '<', now())
        ->whereNull('completed_at')
        ->count();
@endphp

@if ($overdue > 0)
    <x-filament::section
        icon="heroicon-o-exclamation-triangle"
        icon-color="danger"
        heading="{{ $overdue }} {{ str('task')->plural($overdue) }} overdue"
        description="Past their due date and still open across every project."
        class="mb-6"
    >
        <x-filament::button
            tag="a"
            href="{{ \App\Filament\Resources\Tasks\TaskResource::getUrl() }}"
            color="danger"
            size="sm"
        >
            Review them
        </x-filament::button>
    </x-filament::section>
@endif
