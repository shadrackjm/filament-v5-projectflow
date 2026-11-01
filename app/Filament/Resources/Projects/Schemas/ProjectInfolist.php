<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team'),
                TextEntry::make('client.name')
                    ->label('Client')
                    ->placeholder('-'),
                TextEntry::make('lead.name')
                    ->label('Lead')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('priority')
                    ->badge(),
                TextEntry::make('color'),
                TextEntry::make('budget')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('starts_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('due_at')
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('is_archived')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Project $record): bool => $record->trashed()),
            ]);
    }
}
