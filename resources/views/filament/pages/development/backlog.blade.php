<x-filament::page>
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">
            Development • {{ $project->name }} • Backlog
        </div>

        <x-filament::button color="gray" :href="route('filament.admin.pages.dashboard')" tag="a">
            Trocar projeto
        </x-filament::button>
    </div>

    <div class="mt-4">
        {{ $this->table }}
    </div>
</x-filament::page>

