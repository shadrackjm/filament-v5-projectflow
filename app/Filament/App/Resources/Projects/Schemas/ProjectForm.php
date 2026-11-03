<?php

namespace App\Filament\App\Resources\Projects\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    /**
     * No team picker: the tenant is the team, and Filament associates the record for us.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            // scopedUnique respects the tenant scope: two teams may both
                            // have a "website-redesign", but one team may not have two.
                            ->scopedUnique(ignoreRecord: true),

                        Select::make('lead_id')
                            ->label('Project lead')
                            ->options(fn (): array => Filament::getTenant()
                                ->members()
                                ->orderBy('name')
                                ->pluck('name', 'users.id')
                                ->all())
                            ->native(false)
                            ->searchable(),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
