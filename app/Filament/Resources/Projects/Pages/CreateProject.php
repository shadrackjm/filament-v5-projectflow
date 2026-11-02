<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;

class CreateProject extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProjectResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Ownership')
                ->description('Who is this for?')
                ->icon(Heroicon::OutlinedUsers)
                ->columns(2)
                ->schema(ProjectForm::ownershipFields()),

            Step::make('Details')
                ->description('Name it and brief it')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->columns(2)
                ->schema(ProjectForm::detailsFields()),

            Step::make('Planning')
                ->description('Dates and budget')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(2)
                ->schema([
                    ...ProjectForm::statusFields(),
                    ...ProjectForm::planningFields(),
                ]),
        ];
    }
}
