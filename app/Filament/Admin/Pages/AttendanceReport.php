<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Carbon\Carbon;
use UnitEnum;

class AttendanceReport extends Page
{
    protected static ?string $navigationLabel = 'Attendance Report';
    protected static ?string $title = 'Employee Attendance Report';
    protected static string | UnitEnum | null $navigationGroup = 'HR Management';
    protected string $view = 'filament.admin.pages.attendance-report';

    public string $selectedMonth;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    // Helper to generate a list of the last 12 months for the dropdown
    public function getMonths(): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'), // e.g., "October 2023"
            ];
        }
        return $months;
    }

    public function updatedSelectedMonth(string $value): void
    {
        $this->dispatch('month-changed', month: $value);
    }
}
