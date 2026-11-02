<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Who they are')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Company name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('Acme Corporation')
                            ->columnSpanFull(),
                        Select::make('team_id')
                            ->label('Owned by team')
                            ->relationship('team', 'name')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active client')
                            ->default(true)
                            ->inline(false)
                            ->onIcon(Heroicon::Check)
                            ->offIcon(Heroicon::XMark)
                            ->helperText('Inactive clients are hidden from the new-project picker.'),
                    ]),

                Section::make('How to reach them')
                    ->description('None of this is required — add what you have.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->validationMessages([
                                'unique' => 'That email address is already on another client.',
                            ]),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedPhone),

                        TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->prefix('https://')
                            ->suffixIcon(Heroicon::OutlinedGlobeAlt)
                            ->columnSpanFull(),
                    ]),

                Section::make('Notes')
                    ->collapsible()
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(6)
                            ->maxLength(65535)
                            ->placeholder('Anything the team should know before picking up the phone.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
