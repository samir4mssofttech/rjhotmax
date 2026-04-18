<?php

namespace App\Filament\Admin\Pages;

use App\Models\Branch;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterBranch extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Branch';
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

    // protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    // {
    //     return Branch::create($data); // returns Branch, signature is Model
    // }

    protected function handleRegistration(array $data): Branch
    {
        $branch = Branch::create($data);

        $branch->members()->attach(auth()->user());

        return $branch;
    }
}
