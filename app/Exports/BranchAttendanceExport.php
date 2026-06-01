<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BranchAttendanceExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithMapping
{
    public function __construct(
        private int    $branchId,
        private string $branchName,
        private string $fromDate,
        private string $toDate,
    ) {}

    public function collection()
    {
        $employees = Employee::where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $employees->map(function (Employee $emp) {
            $base = Attendance::where('employee_id', $emp->id)
                ->whereDate('date', '>=', $this->fromDate)
                ->whereDate('date', '<=', $this->toDate);

            return [
                'employee' => $emp,
                'present'  => (clone $base)->where('status', 'present')->count(),
                'half_day' => (clone $base)->where('status', 'half_day')->count(),
                // 'absent' removed — calculated in map() now
                'late'     => (clone $base)->where('is_late', true)->count(),
                'overtime' => (clone $base)->sum('overtime'),
            ];
        });
    }

    public function map($row): array
    {
        $from    = Carbon::parse($this->fromDate);
        $to      = Carbon::parse($this->toDate);

        // Count every calendar day in the range (inclusive)
        $totalDays = $from->diffInDays($to) + 1;

        // Absent = total days in range − present − half_day
        // (half_day counts as present, so excluded from absent)
        $absent = $totalDays - $row['present'] - $row['half_day'];
        $absent = max(0, $absent); // never go negative

        return [
            $row['employee']->account_number,
            $row['employee']->name,
            ucwords(str_replace('_', ' ', $row['employee']->designation ?? '—')),
            $totalDays,
            $row['present'],
            $row['half_day'],
            $absent,                          // ← calculated, not queried
            $row['late'],
            round($row['overtime'] / 60, 2),
        ];
    }

    public function headings(): array
    {
        $period = Carbon::parse($this->fromDate)->format('d M Y')
            . ' – '
            . Carbon::parse($this->toDate)->format('d M Y');

        return [
            // Row 1 — report title (merged via styles)
            ['Attendance Report — ' . $this->branchName . ' | ' . $period],
            // Row 2 — column headers
            [
                'Emp ID',
                'Employee Name',
                'Designation',
                'Total Days',
                'Present',
                'Half Day',
                'Absent',
                'Late',
                'Overtime (hrs)',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge title row across all columns
        $sheet->mergeCells('A1:I1');

        return [
            // Title row
            1 => [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Header row
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 28,
            'C' => 20,
            'D' => 12,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 16,
        ];
    }

    public function title(): string
    {
        return Carbon::parse($this->fromDate)->format('M Y') . ' Attendance';
    }
}
