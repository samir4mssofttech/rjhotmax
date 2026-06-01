<x-filament-panels::page>
    {{-- Loading Indicator --}}
    <div wire:loading.delay.long
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/30 backdrop-blur-sm">
        <div class="rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Loading...</p>
        </div>
    </div>

    {{-- Header Card --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="p-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                {{-- Branch Info --}}
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10">
                        <x-heroicon-o-building-office-2 class="h-7 w-7 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ $this->record->name }}
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            Branch Code: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->record->code }}</span>
                        </p>
                    </div>
                </div>

                {{-- Date Picker --}}
                <div class="w-full sm:w-56">
                    <label for="attendance-date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        📅 Attendance Date
                    </label>
                    <input id="attendance-date" type="date" wire:model.live="date" max="{{ now()->format('Y-m-d') }}"
                        class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-500 [&::-webkit-calendar-picker-indicator]:dark:invert" />
                </div>
            </div>

            {{-- Quick Actions & Summary --}}
            <div class="mt-6 flex flex-col gap-4 border-t border-gray-100 pt-5 dark:border-white/5 sm:flex-row sm:items-center sm:justify-between">
                {{-- Quick Mark Buttons --}}
                <div class="flex flex-wrap gap-2">
                    <span class="mr-1 self-center text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Quick:</span>
                    <button type="button" wire:click="markAllAs('present')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20 dark:hover:bg-emerald-500/20">
                        <x-heroicon-m-check class="h-3.5 w-3.5" />
                        All Present
                    </button>
                    <button type="button" wire:click="markAllAs('absent')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-200 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/20">
                        <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                        All Absent
                    </button>
                    <button type="button" wire:click="markAllAs('on_leave')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20 dark:hover:bg-amber-500/20">
                        <x-heroicon-m-briefcase class="h-3.5 w-3.5" />
                        All On Leave
                    </button>
                    <button type="button" wire:click="markAllAs('holiday')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 ring-1 ring-cyan-200 transition hover:bg-cyan-100 dark:bg-cyan-500/10 dark:text-cyan-400 dark:ring-cyan-500/20 dark:hover:bg-cyan-500/20">
                        <x-heroicon-m-sparkles class="h-3.5 w-3.5" />
                        All Holiday
                    </button>
                </div>

                {{-- Summary Badges --}}
                @php
                    $statusCounts = collect($attendances)->countBy('status');
                @endphp
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        Total: <span class="font-bold text-gray-900 dark:text-white">{{ count($attendances) }}</span>
                    </span>
                    @if ($statusCounts->get('present', 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            Present: <span class="font-bold">{{ $statusCounts->get('present', 0) }}</span>
                        </span>
                    @endif
                    @if ($statusCounts->get('absent', 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                            Absent: <span class="font-bold">{{ $statusCounts->get('absent', 0) }}</span>
                        </span>
                    @endif
                    @if ($statusCounts->get('half_day', 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                            Half Day: <span class="font-bold">{{ $statusCounts->get('half_day', 0) }}</span>
                        </span>
                    @endif
                    @if ($statusCounts->get('on_leave', 0) > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                            On Leave: <span class="font-bold">{{ $statusCounts->get('on_leave', 0) }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance Table --}}
    @if (count($attendances) > 0)
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{-- Table Header with Search --}}
            <div class="border-b border-gray-100 px-6 py-4 dark:border-white/5">
                {{-- Title Row --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-gray-400" />
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Employee Attendance Sheet</h3>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}
                    </span>
                </div>

                {{-- Search Row --}}
                <div class="mt-4">
                    <div class="relative w-full sm:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by employee name or ID..."
                            class="block w-full rounded-lg border-gray-300 bg-gray-50 py-2 pl-9 pr-9 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:focus:bg-white/10"
                        />
                        @if ($search)
                            <button
                                wire:click="clearSearch"
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <x-heroicon-m-x-circle class="h-4 w-4" />
                            </button>
                        @endif
                    </div>
                    @if ($search)
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            Found <span class="font-semibold text-gray-700 dark:text-gray-300">{{ count($attendances) }}</span>
                            result(s) for "<span class="font-medium">{{ $search }}</span>"
                            &mdash;
                            <button wire:click="clearSearch" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                clear search
                            </button>
                        </p>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50/75 dark:bg-white/5">
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Employee</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Late</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $index = 1; @endphp
                        @foreach ($attendances as $employeeId => $attendance)
                            <tr class="transition duration-75 hover:bg-gray-50/50 dark:hover:bg-white/[0.02]"
                                wire:key="attendance-row-{{ $employeeId }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-400 dark:text-gray-500">
                                    {{ $index++ }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
                                            {{ strtoupper(substr($attendance['employee_name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $attendance['employee_name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attendance['employee_code'] }}</p>
                                            <div class="mt-0.5 flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                                    {{ $attendance['shift_name'] }}
                                                </span>
                                                @if ($attendance['has_record'])
                                                    <span class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400">● Saved</span>
                                                @else
                                                    <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">○ New</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @php
                                            $statusConfig = [
                                                'present' => ['label' => 'Present', 'title' => 'Present', 'bg' => 'bg-emerald-500', 'ring' => 'ring-emerald-300 dark:ring-emerald-700', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400'],

                                                'absent' => ['label' => 'Absent', 'title' => 'Absent', 'bg' => 'bg-red-500', 'ring' => 'ring-red-300 dark:ring-red-700', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-red-500/10 dark:hover:text-red-400'],

                                                // 'half_day' => ['label' => 'Half Day', 'title' => 'Half Day', 'bg' => 'bg-blue-500', 'ring' => 'ring-blue-300 dark:ring-blue-700', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-blue-500/10 dark:hover:text-blue-400'],

                                                // 'on_leave' => ['label' => 'On Leave', 'title' => 'On Leave', 'bg' => 'bg-amber-500', 'ring' => 'ring-amber-300 dark:ring-amber-700', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-amber-500/10 dark:hover:text-amber-400'],

                                                // 'holiday' => ['label' => 'Holiday', 'title' => 'Holiday', 'bg' => 'bg-cyan-500', 'ring' => 'ring-cyan-300 dark:ring-cyan-700', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-cyan-50 hover:text-cyan-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400'],

                                                // 'weekly_off' => ['label' => 'Weekly Off', 'title' => 'Weekly Off', 'bg' => 'bg-gray-500', 'ring' => 'ring-gray-300 dark:ring-gray-600', 'inactive' => 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'],
                                            ];
                                        @endphp
                                        @foreach ($statusConfig as $statusValue => $config)
                                            <button type="button"
                                                wire:click="$set('attendances.{{ $employeeId }}.status', '{{ $statusValue }}')"
                                                title="{{ $config['title'] }}"
                                                @class([
                                                    'inline-flex px-3 h-8 items-center justify-center rounded-lg text-xs font-bold transition-all duration-150',
                                                    $config['bg'] . ' text-white shadow-sm ring-2 ' . $config['ring'] . ' scale-105' => $attendance['status'] === $statusValue,
                                                    $config['inactive'] => $attendance['status'] !== $statusValue,
                                                ])>
                                                {{ $config['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <button type="button"
                                        wire:click="$set('attendances.{{ $employeeId }}.is_late', {{ $attendance['is_late'] ? 'false' : 'true' }})"
                                        @class([
                                            'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                                            'bg-red-500' => $attendance['is_late'],
                                            'bg-gray-200 dark:bg-gray-700' => !$attendance['is_late'],
                                        ])
                                        role="switch"
                                        aria-checked="{{ $attendance['is_late'] ? 'true' : 'false' }}">
                                        <span @class([
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            'translate-x-5' => $attendance['is_late'],
                                            'translate-x-0' => !$attendance['is_late'],
                                        ])></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" wire:model="attendances.{{ $employeeId }}.remarks"
                                        placeholder="Add remarks..."
                                        class="block w-full min-w-[140px] rounded-lg border-gray-300 bg-white py-1.5 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-600" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Save Footer --}}
            <div class="border-t border-gray-100 px-6 py-4 dark:border-white/5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ count($attendances) }}</span>
                        employees · {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                    </p>
                    <div class="flex gap-3">
                        <a href="{{ \App\Filament\Admin\Resources\Branches\BranchResource::getUrl('index') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            <x-heroicon-m-arrow-left class="h-4 w-4" />
                            Back
                        </a>
                        <button type="button" wire:click="save" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-75 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-offset-gray-900">
                            <span wire:loading.remove wire:target="save">
                                <x-heroicon-m-check-circle class="h-4 w-4" />
                            </span>
                            <x-filament::loading-indicator wire:loading wire:target="save" class="h-4 w-4" />
                            Save Attendance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col items-center justify-center px-6 py-16">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <x-heroicon-o-user-group class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                </div>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">No Active Employees</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($search)
                        No employees found matching "<strong>{{ $search }}</strong>".
                        <button wire:click="clearSearch" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Clear search</button>
                    @else
                        There are no active employees assigned to this branch.
                    @endif
                </p>
                <a href="{{ \App\Filament\Admin\Resources\Branches\BranchResource::getUrl('index') }}"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500">
                    <x-heroicon-m-arrow-left class="h-4 w-4" />
                    Back to Branches
                </a>
            </div>
        </div>
    @endif
</x-filament-panels::page>
