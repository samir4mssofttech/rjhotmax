<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected array $data;
    protected string $month;

    public function __construct(array $data, string $month)
    {
        $this->data = $data;
        $this->month = $month;
    }

    public function headings(): array
    {
        return [
            'Sl No',
            'Employee Name',
            'Account No',
            'IFSC Code',
            'Bank Name',
            'Amount (₹)',
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 25,
            'C' => 22,
            'D' => 18,
            'E' => 22,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
