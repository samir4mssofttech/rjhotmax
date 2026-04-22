<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Attendance;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
// CHANGE THIS IMPORT:
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class AttendanceReportWidget extends BaseWidget implements HasForms, HasTable
{
    protected static bool $isDiscovered = false;

    use InteractsWithForms, InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        // Set default values to current month and year
        $this->form->fill([
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    // UPDATE THIS METHOD SIGNATURE
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // Use ->components() instead of ->schema()
                Select::make('branch_id')
                    ->label('Select Branch')
                    ->options(Branch::pluck('name', 'id'))
                    ->placeholder('All Branches')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn() => $this->resetTable()),

            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()->when($this->data['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            )
            // Inside your table() method:
            ->recordUrl(
                fn(Employee $record): string => route('filament.admin.pages.employee-attendance-details', [
                    'employee' => $record->id // This will now be passed as ?employee=ID
                ])
            )->filters([])
            ->columns([
                TextColumn::make('account_number')
                    ->label('Emp. Code')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Employee')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable(),
                TextColumn::make('attendance_visual')
                    ->label('Daily Status (1-31)')
                    ->html()
                    ->state(function (Employee $record) {
                        $now = Carbon::now();
                        $today = $now->day; // Get current day (e.g., 22)
                        $daysInMonth = $now->daysInMonth;

                        $attendance = Attendance::where('employee_id', $record->id)
                            ->whereMonth('date', $now->month)
                            ->pluck('status', 'date')
                            ->toArray();

                        $html = '<div class="flex gap-1">';

                        for ($i = 1; $i <= $daysInMonth; $i++) {
                            $date = $now->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);

                            // 1. Check if the date is in the future (Future Dates = Blue)
                            if ($i > $today) {
                                $color = 'bg-blue-500';
                                $title = "Future Date (Day $i)";
                            } else {
                                // 2. Date has passed or is today: check status
                                $status = $attendance[$date] ?? 'absent'; // Default to absent if no record

                                // Enum Safety Check: Convert Enum to string value if necessary
                                $statusValue = ($status instanceof \BackedEnum) ? $status->value : $status;

                                $color = ($statusValue === 'present') ? 'bg-green-500' : 'bg-red-500';
                                $title = "Day $i: " . ucfirst($statusValue);
                            }

                            $html .= "<span title='$title' class='w-2 h-2 rounded-full $color'></span>";
                        }

                        $html .= '</div>';
                        return $html;
                    }),

                TextColumn::make('total_present')
                    ->label('Present')
                    ->state(
                        fn(Employee $record) =>
                        Attendance::where('employee_id', $record->id)
                            ->whereMonth('date', Carbon::now()->month)
                            ->where('status', 'present')
                            ->count()
                    )
                    ->badge()
                    ->color('success'),

                TextColumn::make('percentage')
                    ->label('Rate')
                    ->state(function (Employee $record) {
                        $total = Carbon::now()->daysInMonth;
                        $present = Attendance::where('employee_id', $record->id)
                            ->whereMonth('date', Carbon::now()->month)
                            ->where('status', 'present')
                            ->count();
                        return round(($present / $total) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn($state) => floatval($state) < 75 ? 'danger' : 'success'),
            ]);
    }
}
