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
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Components\Grid;


class AttendanceReportWidget extends BaseWidget implements HasForms, HasTable
{
    protected static bool $isDiscovered = false;

    use InteractsWithForms, InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        // Set default values to current month's start and today
        $this->form->fill([
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]);
    }

    // UPDATE THIS METHOD SIGNATURE
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // Use ->components() instead of ->schema()
                Grid::make(2)->schema([
                    DatePicker::make('from_date')
                        ->label('From Date')
                        ->live()
                        ->afterStateUpdated(fn() => $this->resetTable()),
                    DatePicker::make('to_date')
                        ->label('To Date')
                        ->live()
                        ->afterStateUpdated(fn() => $this->resetTable()),
                ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Branch::query())
            ->recordUrl(
                fn(Branch $record): string => route('filament.admin.pages.branch-employee-attendance', [
                    'branch' => $record->id,
                    'from_date' => $this->data['from_date'] ?? null,
                    'to_date' => $this->data['to_date'] ?? null,
                ])
            )
            // ->filters([
            //     Filter::make('created_at')
            //         ->form([
            //             DatePicker::make('created_at')
            //                 ->label('Select Date')
            //                 ->live()
            //                 ->afterStateUpdated(fn() => $this->resetTable()),
            //         ])
            //         ->query(function ($query, array $data) {
            //             return $query
            //                 ->when(
            //                      $data['created_at'],
            //                     fn($q) =>
            //                     $q->whereDate('created_at', $data['created_at'])
            //                 );
            //         }),
            // ])
            ->columns([
                TextColumn::make('name')
                    ->label('Branch Name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Branch Code')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('total_employees')
                    ->label('Total Employees')
                    ->state(function (Branch $record) {
                        return Employee::where('branch_id', $record->id)->count();
                    }),
                TextColumn::make('total_present')
                    ->label('Present')
                    ->state(function (Branch $record) {
                        return Attendance::where('branch_id', $record->id)
                            ->when(!empty($this->data['from_date']), fn($q) => $q->whereDate('date', '>=', $this->data['from_date']))
                            ->when(!empty($this->data['to_date']), fn($q) => $q->whereDate('date', '<=', $this->data['to_date']))
                            ->where('status', 'present')
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('total_absent')
                    ->label('Absent')
                    ->state(function (Branch $record) {
                        return Attendance::where('branch_id', $record->id)
                            ->when(!empty($this->data['from_date']), fn($q) => $q->whereDate('date', '>=', $this->data['from_date']))
                            ->when(!empty($this->data['to_date']), fn($q) => $q->whereDate('date', '<=', $this->data['to_date']))
                            ->where('status', 'absent')
                            ->count();
                    })
                    ->badge()
                    ->color('danger'),
            ]);
    }
}
