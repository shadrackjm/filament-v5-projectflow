@if (! app()->environment('production'))
    <div class="projectflow-banner">
        <x-filament::icon icon="heroicon-o-beaker" class="h-4 w-4" />

        <span>
            You are on <strong>{{ str(app()->environment())->upper() }}</strong> — nothing here is real data.
        </span>
    </div>
@endif
