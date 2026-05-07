<?php

namespace App\Filament\Admin\Pages;

use App\Models\Employee;
use App\Models\Attendance;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Carbon\Carbon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use App\Enums\Attendance as AttendanceEnum;
use Filament\Forms\Components\ToggleButtons;

class EmployeeAttendanceDetails extends Page implements HasTable
{
    use InteractsWithTable;

    // ❗ must be `protected static string $view` (static), not `protected string $view`
    protected string $view = 'filament.admin.pages.employee-attendance-details';

    protected static ?string $title = 'Employee Attendance Details';

    protected static bool $shouldRegisterNavigation = false;

    // ✅ Declare ALL three as public properties so Blade can access them
    public ?Employee $employee = null;
    public ?string $from_date = null;
    public ?string $to_date = null;

    public function mount(): void
    {
        $employeeId = request()->query('employee');
        $this->from_date = request()->query('from_date', now()->startOfMonth()->format('Y-m-d'));
        $this->to_date   = request()->query('to_date', now()->format('Y-m-d'));

        if (! $employeeId) {
            abort(404, 'Employee ID not provided.');
        }

        $this->employee = Employee::with('branch')->findOrFail($employeeId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Attendance::query()
                    ->where('employee_id', $this->employee->id)
                    ->when($this->from_date, fn($q) => $q->whereDate('date', '>=', $this->from_date))
                    ->when($this->to_date,   fn($q) => $q->whereDate('date', '<=', $this->to_date))
                    ->orderBy('date', 'desc')
            )
            ->columns([
                TextColumn::make('date')
                    ->date('d M, Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state?->value) {
                        'present'    => 'success',
                        'absent'     => 'danger',
                        'half_day'   => 'info',
                        'on_leave'   => 'warning',
                        'holiday'    => 'info',
                        'weekly_off' => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn($state) => strtoupper($state?->value ?? '')),

                // ❗ check_in_time / check_out_time are TIME columns, use ->time() not ->date()
                // TextColumn::make('check_in_time')
                //     ->time('H:i')
                //     ->label('Logged-In')
                //     ->default('--:--'),

                // TextColumn::make('check_out_time')
                //     ->time('H:i')
                //     ->label('Logged-Out')
                //     ->default('--:--'),

                TextColumn::make('is_late')
                    ->label('Late')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                TextColumn::make('remarks')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (! $state || strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Attendance')
                    ->form([
                        // Select::make('status')
                        //     ->options(
                        //         collect(AttendanceEnum::cases())
                        //             ->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])
                        //             ->toArray()
                        //     )
                        //     ->required(),
                        ToggleButtons::make('status')
                            ->options(AttendanceEnum::class)
                            ->inline()
                            ->grouped()
                            ->default(AttendanceEnum::PRESENT)
                            ->required(),
                        // TimePicker::make('check_in_time'),
                        // TimePicker::make('check_out_time'),
                        Toggle::make('is_late')->label('Mark as Late'),
                        Textarea::make('remarks')->rows(2),
                    ]),
            ]);
    }
}
