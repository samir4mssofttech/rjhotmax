<x-filament-panels::page>
    <div>

        {{-- Summary Stats Strip --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">

            {{-- Total Employee IDs --}}
            <div class="rounded-xl border p-5 shadow-sm flex items-center gap-4 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300">
                    <x-heroicon-o-identification class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Employee IDs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Employee::count() }}</p>
                </div>
            </div>

            {{-- Total Branches --}}
            <div class="rounded-xl border p-5 shadow-sm flex items-center gap-4 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300">
                    <x-heroicon-o-building-office class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Branches</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Branch::count() }}</p>
                </div>
            </div>

            {{-- Present Today --}}
            <div class="rounded-xl border p-5 shadow-sm flex items-center gap-4 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300">
                    <x-heroicon-o-check-circle class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Present Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\Attendance::whereDate('date', now())->where('status', 'present')->count() }}
                    </p>
                </div>
            </div>

            {{-- Absent Today --}}
            <div class="rounded-xl border p-5 shadow-sm flex items-center gap-4 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300">
                    <x-heroicon-o-x-circle class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Absent Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\Attendance::whereDate('date', now())->where('status', 'absent')->count() }}
                    </p>
                </div>
            </div>

        </div>

        {{-- Main Content Area --}}
        <div class="mt-6">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">

                {{-- Header: title + month picker --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <h3 class="text-lg font-bold dark:text-white">Monthly Attendance Matrix</h3>

                    <div class="flex items-center gap-2">
                        <label for="month-picker" class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            Select Month:
                        </label>
                        <input
                            id="month-picker"
                            type="month"
                            wire:model.live="selectedMonth"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                                   text-gray-900 dark:text-white text-sm px-3 py-1.5 shadow-sm
                                   focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>

                {{-- Widget --}}
                @livewire(\App\Filament\Admin\Widgets\AttendanceReportWidget::class)

            </div>
        </div>

    </div>
</x-filament-panels::page>