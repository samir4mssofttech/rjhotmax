<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AttendanceReport extends Page
{

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Attendance Report';
    protected static ?string $title = 'Employee Attendance Report';
    protected static string | UnitEnum | null $navigationGroup = 'HR Management';

    protected string $view = 'filament.admin.pages.attendance-report';
}
