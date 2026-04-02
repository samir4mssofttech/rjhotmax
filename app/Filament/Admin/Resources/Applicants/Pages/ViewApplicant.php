<?php

namespace App\Filament\Admin\Resources\Applicants\Pages;

use App\Filament\Admin\Resources\Applicants\ApplicantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApplicant extends ViewRecord
{
    protected static string $resource = ApplicantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
