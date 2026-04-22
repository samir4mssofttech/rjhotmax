<?php

namespace App\Filament\Admin\Pages;

use App\Models\Employee;
use App\Models\Attendance;
use Filament\Pages\Page;
use Carbon\Carbon;

class EmployeeAttendanceDetails extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected string $view = 'filament.admin.pages.employee-attendance-details';
    protected static ?string $title = 'Employee Attendance Details';
    
    // Hide from sidebar since we only access it via clicking the table
    protected static bool $shouldRegisterNavigation = false;

    public Employee $employee;
    public $attendanceRecords;

     // REMOVE the $employee argument from here
    public function mount(): void
    {
        // 1. Get the employee ID from the URL query string (?employee=XX)
        $employeeId = request()->query('employee');

        if (!$employeeId) {
            // Redirect back or show 404 if no ID is provided
            abort(404, 'Employee ID not provided.');
        }

        // 2. Fetch the employee
        $this->employee = Employee::findOrFail($employeeId);

        // 3. Fetch attendance for current month
        $this->attendanceRecords = Attendance::where('employee_id', $this->employee->id)
            ->whereMonth('date', Carbon::now()->month)
            ->orderBy('date', 'asc')
            ->get();
    }
}