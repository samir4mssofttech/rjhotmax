<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use UnitEnum;

class AttendanceReport extends Page
{

    protected static ?string $navigationLabel = 'Attendance Report';
    protected static ?string $title = 'Employee Attendance Report';
    protected static string | UnitEnum | null $navigationGroup = 'HR Management';

    protected string $view = 'filament.admin.pages.attendance-report';
}
