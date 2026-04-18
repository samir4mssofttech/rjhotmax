<?php

namespace App\Filament\Admin\Resources\Employees;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Employees\Infolists\EmployeeInfolist;
use App\Filament\Admin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Employees\Pages\ListEmployees;
use App\Filament\Admin\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Admin\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Admin\Resources\Employees\Schemas\EmployeeInfolist as SchemasEmployeeInfolist;
use App\Filament\Admin\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $label = 'Employee';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::HRMS;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchemasEmployeeInfolist::configure($schema);
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
            'index' => ListEmployees::route('/'),
            // 'create' => CreateEmployee::route('/create'),
            // 'edit' => EditEmployee::route('/{record}/edit'),
            'view' => ViewEmployee::route('/{record}'),

        ];
    }
}
