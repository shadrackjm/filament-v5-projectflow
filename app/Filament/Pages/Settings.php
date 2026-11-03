<?php

namespace App\Filament\Pages;

use App\Enums\Priority;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Studio settings';

    public const CACHE_KEY = 'projectflow.settings';

    /**
     * Public so Livewire can bind the schema state to it.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Only administrators see this page in navigation, and only they can open it.
     */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        return Cache::get(static::CACHE_KEY, [
            'default_priority' => Priority::Medium->value,
            'working_hours_per_day' => 7.5,
            'default_hourly_rate' => 120,
            'require_time_notes' => false,
            'overdue_warning_days' => 14,
        ]);
    }

    public function mount(): void
    {
        $this->form->fill(static::current());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Project defaults')
                    ->description('Applied to new projects and tasks unless someone overrides them.')
                    ->columns(2)
                    ->schema([
                        Select::make('default_priority')
                            ->options(Priority::class)
                            ->native(false)
                            ->required(),

                        TextInput::make('overdue_warning_days')
                            ->label('Warn this many days before a deadline')
                            ->numeric()->minValue(1)->maxValue(90)
                            ->suffix('days')
                            ->required(),
                    ]),

                Section::make('Time & billing')
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours_per_day')
                            ->numeric()->minValue(1)->maxValue(24)->step(0.5)
                            ->suffix('hrs')
                            ->required(),

                        TextInput::make('default_hourly_rate')
                            ->numeric()->minValue(0)->prefix('$')
                            ->required(),

                        Toggle::make('require_time_notes')
                            ->label('Require a note on every time entry')
                            ->inline(false)
                            ->helperText('Useful when you bill clients from these logs.'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save settings')->action('save'),

            Action::make('reset')
                ->label('Reset to defaults')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    Cache::forget(static::CACHE_KEY);
                    $this->form->fill(static::current());

                    Notification::make()->title('Settings reset')->success()->send();
                }),
        ];
    }

    public function save(): void
    {
        Cache::forever(static::CACHE_KEY, $this->form->getState());

        Notification::make()->title('Settings saved')->success()->send();
    }
}
