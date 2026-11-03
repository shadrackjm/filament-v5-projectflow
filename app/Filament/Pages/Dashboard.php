<?php

namespace App\Filament\Pages;

use App\Models\Team;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filters')
                ->collapsible()
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Select::make('team_id')
                        ->label('Team')
                        ->placeholder('All teams')
                        ->options(fn (): array => Team::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->native(false)
                        ->searchable(),

                    DatePicker::make('from')
                        ->label('Logged from')
                        ->native(false)
                        ->default(now()->startOfMonth()),

                    DatePicker::make('until')
                        ->label('Logged until')
                        ->native(false)
                        ->default(now()->endOfMonth()),
                ]),
        ]);
    }
}
