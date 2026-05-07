<?php

namespace App\Filament\Admin\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Attendance;
use Filament\Pages\Page;
use Carbon\Carbon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class BranchEmployeeAttendance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Branch Employees Attendance';
    protected string $view = 'filament.admin.pages.branch-employee-attendance';
    protected static bool $shouldRegisterNavigation = false;

    public $branch_id;
    public $from_date;
    public $to_date;
    public Branch $branch;

    public function mount(): void
    {
        $this->branch_id = request()->query('branch');
        $this->from_date = request()->query('from_date', now()->startOfMonth()->format('Y-m-d'));
        $this->to_date = request()->query('to_date', now()->format('Y-m-d'));

        if (!$this->branch_id) {
            abort(404, 'Branch ID not provided.');
        }

        $this->branch = Branch::findOrFail($this->branch_id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Employee::query()->where('branch_id', $this->branch->id))
            ->recordUrl(fn (Employee $record): string => route('filament.admin.pages.employee-attendance-details', [
                'employee' => $record->id,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
            ]))
            ->columns([
                TextColumn::make('account_number')->label('Emp. Code')->searchable()->weight('bold'),
                TextColumn::make('name')->label('Employee')->searchable()->weight('bold'),
                TextColumn::make('total_present')
                    ->label('Present')
                    ->state(function (Employee $record) {
                        return Attendance::where('employee_id', $record->id)
                            ->when(!empty($this->from_date), fn($q) => $q->whereDate('date', '>=', $this->from_date))
                            ->when(!empty($this->to_date), fn($q) => $q->whereDate('date', '<=', $this->to_date))
                            ->where('status', 'present')
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('total_absent')
                    ->label('Absent')
                    ->state(function (Employee $record) {
                        return Attendance::where('employee_id', $record->id)
                            ->when(!empty($this->from_date), fn($q) => $q->whereDate('date', '>=', $this->from_date))
                            ->when(!empty($this->to_date), fn($q) => $q->whereDate('date', '<=', $this->to_date))
                            ->where('status', 'absent')
                            ->count();
                    })
                    ->badge()
                    ->color('danger'),
                TextColumn::make('percentage')
                    ->label('Rate')
                    ->state(function (Employee $record) {
                        $from = !empty($this->from_date) ? Carbon::parse($this->from_date) : now()->startOfMonth();
                        $to = !empty($this->to_date) ? Carbon::parse($this->to_date) : now();
                        
                        $daysDiff = $from->diffInDays($to) + 1;
                        if ($daysDiff <= 0) return '0%';

                        $present = Attendance::where('employee_id', $record->id)
                            ->whereDate('date', '>=', $from)
                            ->whereDate('date', '<=', $to)
                            ->where('status', 'present')
                            ->count();
                            
                        return round(($present / $daysDiff) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn($state) => floatval($state) < 75 ? 'danger' : 'success'),
            ]);
    }
}
