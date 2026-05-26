<?php

namespace App\Filament\Admin\Resources\Applicants\Pages;

use App\Filament\Admin\Resources\Applicants\ApplicantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApplicant extends EditRecord
{
    protected static string $resource = ApplicantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
