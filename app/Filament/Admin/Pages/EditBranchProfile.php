<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditBranchProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Branch profile';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('code')->unique('branches', 'code'),
                TextInput::make('location'),
            ]);
    }
}
