<?php

namespace App\Filament\Admin\Resources\Branches\Pages;

use App\Enums\Attendance as AttendanceEnum;
use App\Filament\Admin\Resources\Branches\BranchResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class TakeAttendance extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BranchResource::class;

    protected string $view = 'filament.admin.resources.branches.pages.take-attendance';

    protected static ?string $title = 'Take Attendance';

    public string $date;

    public string $search = '';

    public array $attendances = [];

    public array $shifts = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->date = now()->format('Y-m-d');
        $this->shifts = Shift::pluck('name', 'id')->toArray();
        $this->loadAttendances();
    }

    public function updatedDate(): void
    {
        $this->loadAttendances();
    }

    public function updatedSearch(): void
    {
        $this->loadAttendances();
    }

    public function loadAttendances(): void
    {
        $employees = Employee::where('branch_id', $this->record->id)
            ->where('is_active', true)
            ->where('employee_status', '!=', 'exit')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('id', 'like', '%' . $this->search . '%')
                      ->orWhere('id', $this->search);
                });
            })
            ->orderBy('name')
            ->get();

        $existingAttendances = Attendance::where('branch_id', $this->record->id)
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('employee_id');

        $this->attendances = [];

        foreach ($employees as $employee) {
            $existing = $existingAttendances->get($employee->id);

            $this->attendances[$employee->id] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'employee_code' => $employee->account_number,
                'shift_name' => $employee->shift?->name ?? 'No Shift Assigned',
                'shift_id' => $existing?->shift_id ?? $employee->shift_id,
                'status' => $existing?->status?->value ?? 'present',
                'is_late' => (bool) ($existing?->is_late ?? false),
                'remarks' => $existing?->remarks ?? '',
                'has_record' => $existing !== null,
            ];
        }
    }

    public function markAllAs(string $status): void
    {
        foreach ($this->attendances as $id => $attendance) {
            $this->attendances[$id]['status'] = $status;
        }
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->loadAttendances();
    }

    public function save(): void
    {
        $savedCount = 0;

        foreach ($this->attendances as $data) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $data['employee_id'],
                    'date' => $this->date,
                ],
                [
                    'branch_id' => $this->record->id,
                    'shift_id' => $data['shift_id'] ?: null,
                    'status' => $data['status'],
                    'is_late' => $data['is_late'],
                    'remarks' => $data['remarks'] ?: null,
                    'entered_by' => auth()->id(),
                ]
            );

            $savedCount++;
        }

        $this->loadAttendances();

        Notification::make()
            ->title("Attendance saved for {$savedCount} employees")
            ->success()
            ->send();
    }

    public function getHeading(): string
    {
        return 'Take Attendance';
    }

    public function getSubheading(): ?string
    {
        return $this->record->name . ' (' . $this->record->code . ')';
    }

    public function getStatusOptions(): array
    {
        return collect(AttendanceEnum::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getStatusColors(): array
    {
        return [
            'present' => 'emerald',
            'absent' => 'red',
            'half_day' => 'blue',
            'on_leave' => 'amber',
            'holiday' => 'cyan',
            'weekly_off' => 'gray',
        ];
    }
}
