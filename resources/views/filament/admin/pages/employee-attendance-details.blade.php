<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Employee Profile Header --}}
        <x-filament::section>
            <div class="flex items-center gap-6">
                <div class="h-20 w-20 rounded-full bg-gray-200 overflow-hidden border-4 border-primary-500">
                    @if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo))
                        <img src="{{ Storage::disk('public')->url($employee->profile_photo) }}"
                            alt="{{ $employee->name }}" class="h-full w-full object-cover">
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
                <h3 class="text-lg font-bold">Attendance Logs</h3>
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                    {{ \Carbon\Carbon::parse($from_date)->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($to_date)->format('d M Y') }}
                </span>
            </div>

            <div>
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
