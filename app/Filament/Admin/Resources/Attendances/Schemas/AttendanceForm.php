<?php

namespace App\Filament\Admin\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\ShiftType;
use App\Models\Shift;
use Carbon\Carbon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee & Shift')
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('shift_id')
                            ->relationship('shift', 'type')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Grid::make(3)->schema([
                                    Select::make('type')
                                        ->options(ShiftType::class)
                                        ->required(),

                                    TimePicker::make('start_time')
                                        ->seconds(false)
                                        ->required(),

                                    TimePicker::make('end_time')
                                        ->seconds(false)
                                        // ->after('start_time')
                                        ->required(),

                                    TextInput::make('grace_period_minutes')
                                        ->numeric()
                                        ->default(0),

                                    TextInput::make('half_day_minutes')
                                        ->numeric()
                                        ->default(0),

                                    ToggleButtons::make('overtime_eligible')
                                        ->options([
                                            true => 'Yes',
                                            false => 'No',
                                        ])
                                        ->default(false)
                                        ->inline()
                                        ->required(),

                                    Toggle::make('is_active')
                                        ->default(true)
                                        ->inline()
                                        ->required(),
                                ]),
                            ]),

                        DatePicker::make('date')
                            ->default(now())
                            ->required(),
                    ]),


                Section::make('Time & Attendance')
                    ->columns(2)
                    ->schema([
                        TimePicker::make('check_in_time')
                            ->seconds(false)
                            ->live(), // Added Live

                        TimePicker::make('check_out_time')
                            ->seconds(false)
                            ->live(), // Added Live

                        // Dynamically calculated Worked Minutes
                        Placeholder::make('worked_hours_placeholder')
                            ->label('Worked Hours')
                            ->content(function ($get) {
                                $in = $get('check_in_time');
                                $out = $get('check_out_time');
                                if (!$in || !$out) return '—';

                                $workedMinutes = self::calculateWorkedMinutes($in, $out);
                                return number_format($workedMinutes / 60, 2) . ' hrs';
                            }),

                        // Dynamically calculated Overtime
                        Placeholder::make('overtime_placeholder')
                            ->label('Overtime / Shortage')
                            ->helperText('Positive = Overtime, Negative = Undertime')
                            ->content(function ($get) {
                                $in = $get('check_in_time');
                                $out = $get('check_out_time');
                                $shiftId = $get('shift_id');

                                if (!$in || !$out || !$shiftId) return '—';

                                $diffMinutes = self::calculateOvertimeMinutes($in, $out, $shiftId);
                                $hours = number_format($diffMinutes / 60, 2);

                                $color = $diffMinutes >= 0 ? 'text-success-600' : 'text-danger-600';
                                return new \Illuminate\Support\HtmlString("<span class='{$color} font-bold'>{$hours} hrs</span>");
                            }),

                    ]),
                Section::make('Status & Flags')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('status')
                            ->options([
                                'present'     => 'Present',
                                'absent'      => 'Absent',
                                'half_day'    => 'Half Day',
                                'on_leave'    => 'On Leave',
                                'holiday'     => 'Holiday',
                                'weekly_off'  => 'Weekly Off',
                            ])
                            ->colors([
                                'present'    => 'success',
                                'absent'     => 'danger',
                                'half_day'   => 'info',
                                'on_leave'   => 'warning',
                                'holiday'    => 'gray',
                                'weekly_off' => 'gray',
                            ])
                            ->default('present')
                            ->grouped()
                            ->required()
                            ->columnSpanFull(),

                        ToggleButtons::make('is_late')
                            ->label('Marked Late?')
                            ->boolean()
                            ->grouped()
                            ->default(false),
                    ]),

                Section::make('Remarks')
                    ->schema([
                        Textarea::make('remarks')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),
                Hidden::make('entered_by')
                    ->default(fn() => Auth()->id()),
            ]);
    }


    /**
     * Helper to calculate actual minutes worked
     */
    protected static function calculateWorkedMinutes($in, $out): int
    {
        $startTime = Carbon::parse($in);
        $endTime = Carbon::parse($out);

        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }

        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Helper to calculate (Worked Minutes) - (Shift Required Minutes)
     */
    protected static function calculateOvertimeMinutes($in, $out, $shiftId): int
    {
        $actualMinutes = self::calculateWorkedMinutes($in, $out);

        $shift = Shift::find($shiftId);
        if (!$shift) return 0;

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $requiredMinutes = $shiftStart->diffInMinutes($shiftEnd);

        return $actualMinutes - $requiredMinutes;
    }

    /**
     * Updates the 'is_late' toggle automatically based on worked hours
     */
    protected static function updateLateStatus(Set $set, $get)
    {
        $in = $get('check_in_time');
        $out = $get('check_out_time');
        $shiftId = $get('shift_id');

        if ($in && $out && $shiftId) {
            $diff = self::calculateOvertimeMinutes($in, $out, $shiftId);
            // If negative (undertime), set is_late to true
            $set('is_late', $diff < 0);
        }
    }
}
