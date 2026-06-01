<?php

namespace App\Filament\Admin\Widgets;

use App\Exports\BranchAttendanceExport;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceReportWidget extends BaseWidget implements HasForms, HasTable
{
    protected static bool $isDiscovered = false;

    use InteractsWithForms, InteractsWithTable;

    public ?array $data = [];

    protected $listeners = ['month-changed' => 'applyMonth'];

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date'   => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function applyMonth(string $month): void
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $this->form->fill([
            'from_date' => $start->format('Y-m-d'),
            'to_date'   => $end->format('Y-m-d'),
        ]);

        $this->resetTable();
    }

    // ── Called by the row action ──────────────────────────────────
    public function exportBranch(int $branchId): BinaryFileResponse
    {
        $branch   = Branch::findOrFail($branchId);
        $from     = $this->data['from_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $to       = $this->data['to_date']   ?? now()->endOfMonth()->format('Y-m-d');
        $filename = str($branch->name)->slug() . '_attendance_' . Carbon::parse($from)->format('Y-m') . '.xlsx';

        return Excel::download(
            new BranchAttendanceExport($branch->id, $branch->name, $from, $to),
            $filename
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    'branch'     => $record->id,
                    'from_date'  => $this->data['from_date'] ?? null,
                    'to_date'    => $this->data['to_date']   ?? null,
                ])
            )
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
                    ->state(fn(Branch $record) => Employee::where('branch_id', $record->id)->count()),
                TextColumn::make('total_present')
                    ->label('Present')
                    ->state(function (Branch $record) {
                        return Attendance::where('branch_id', $record->id)
                            ->when(!empty($this->data['from_date']), fn($q) => $q->whereDate('date', '>=', $this->data['from_date']))
                            ->when(!empty($this->data['to_date']),   fn($q) => $q->whereDate('date', '<=', $this->data['to_date']))
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
                            ->when(!empty($this->data['to_date']),   fn($q) => $q->whereDate('date', '<=', $this->data['to_date']))
                            ->where('status', 'absent')
                            ->count();
                    })
                    ->badge()
                    ->color('danger'),
            ])
            // ── Download button on each row ───────────────────────
            ->actions([
                Action::make('export')
                    ->label('Download Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn(Branch $record) => $this->exportBranch($record->id)),
            ]);
    }
}