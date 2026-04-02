<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class DepartmentChart extends ChartWidget
{
    // protected static ?string $heading = 'Staff Distribution';
    // protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => [45, 25, 15, 10, 5], // Static hard-coded values
                    'backgroundColor' => [
                        '#0ea5e9', // Engineering
                        '#8b5cf6', // Marketing
                        '#ec4899', // Sales
                        '#f59e0b', // HR
                        '#64748b', // Admin
                    ],
                ],
            ],
            'labels' => ['Workers', 'Marketing', 'Sales', 'HR', 'Admin'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getColumns(): int | array
{
    return [
        'md' => 4,
        'xl' => 5,
    ];
}
}