<?php

namespace App\Filament\Admin\Resources\Applicants\Pages;

use App\Filament\Admin\Resources\Applicants\ApplicantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicant extends CreateRecord
{
    protected static string $resource = ApplicantResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
