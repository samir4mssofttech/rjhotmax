<?php

namespace App\Filament\Admin\Resources\Applicants;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Applicants\Pages\CreateApplicant;
use App\Filament\Admin\Resources\Applicants\Pages\EditApplicant;
use App\Filament\Admin\Resources\Applicants\Pages\ListApplicants;
use App\Filament\Admin\Resources\Applicants\Pages\ViewApplicant;
use App\Filament\Admin\Resources\Applicants\Schemas\ApplicantForm;
use App\Filament\Admin\Resources\Applicants\Schemas\ApplicantInfolist;
use App\Filament\Admin\Resources\Applicants\Tables\ApplicantsTable;
use App\Models\Applicant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ApplicantResource extends Resource
{
    protected static ?string $model = Applicant::class;

    protected static ?string $navigationLabel = 'Applicant Details';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::HRMS;

    protected static ?string $recordTitleAttribute = 'applicant_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['applicant_name', 'applicant_code', 'email_id', 'mobile_number'];
    }

    public static function form(Schema $schema): Schema
    {
        return ApplicantForm::configure($schema);
    }

    public static function infolist(Schema $schema):Schema {
        return ApplicantInfolist::configure($schema);
    }
    public static function table(Table $table): Table
    {
        return ApplicantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplicants::route('/'),
            'create' => CreateApplicant::route('/create'),
            'edit' => EditApplicant::route('/{record}/edit'),
            'view' => ViewApplicant::route('/{record}'),

        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
