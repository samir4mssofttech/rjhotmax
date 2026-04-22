<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Employee Profile Header --}}
        <x-filament::section>
            <div class="flex items-center gap-6">
                <div class="h-20 w-20 rounded-full bg-gray-200 overflow-hidden border-4 border-primary-500">
                    @if ($employee->profile_photo)
                        <img src="{{ asset('storage/app/private/' . $employee->profile_photo) }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-gray-400">
                            <x-heroicon-o-user class="h-10 w-10" />
                        </div>
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
                    <div class="flex gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-hashtag class="h-4 w-4" />
                            <strong>Code: </strong> {{ $employee->account_number ?? 'N/A' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-building-office class="h-4 w-4" />
                            <strong>Branch:</strong> {{ $employee->branch?->name ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Attendance Logs Table --}}
        <x-filament::section>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Attendance Logs (Current Month)</h3>
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                    {{ \Carbon\Carbon::now()->format('F Y') }}
                </span>
            </div>

            <div class="overflow-x-auto border rounded-xl">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="p-3 border-b">Date</th>
                            <th class="p-3 border-b">Status</th>
                            <th class="p-3 border-b">Logged-In</th>
                            <th class="p-3 border-b">Logged-Out</th>
                            <th class="p-3 border-b">Work Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($attendanceRecords as $record)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-3 font-medium">{{ \Carbon\Carbon::parse($record->date)->format('d M, Y') }}
                                </td>
                                <td class="p-3">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold {{ $record->status === 'present' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ strtoupper($record->status->value) }} </span>
                                </td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">{{ $record->check_in_time ?? '--:--' }}</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">{{ $record->check_out_time ?? '--:--' }}
                                </td>
                                <td class="p-3 font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $record->work_hours ?? '0h 0m' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-500">No attendance records found
                                    for this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
