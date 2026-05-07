<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-building-office class="h-6 w-6 text-primary-500" />
                    {{ $branch->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Branch Code: <span class="font-bold">{{ $branch->code }}</span>
                </p>
            </div>
            
            <div class="px-4 py-2 rounded-xl bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold border border-primary-100 dark:border-primary-800 flex items-center gap-2">
                <x-heroicon-o-calendar class="h-5 w-5" />
                {{ \Carbon\Carbon::parse($from_date)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to_date)->format('d M Y') }}
            </div>
        </div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
