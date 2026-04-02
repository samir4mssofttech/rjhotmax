<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Talent Pool', '1,284')
                ->description('32 New this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]), // Static sparkline chart

            Stat::make('Average Retention', '94.2%')
                ->description('2% increase from Q3')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([10, 10, 10, 12, 11, 13, 14, 14]),

            Stat::make('Open Positions', '12')
                ->description('4 high priority')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('warning')
                ->chart([2, 5, 3, 6, 4, 3, 4, 2]),
        ];
    }
}