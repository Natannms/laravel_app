<x-filament::page>
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">
            {{ $record->name }} — Lista
        </div>

        <x-filament::button
            color="gray"
            :href="\App\Filament\Resources\ProjectResource::getUrl('kanban', ['record' => $record])"
            tag="a"
        >
            Visualizar em Kanban
        </x-filament::button>
    </div>

    <div class="mt-4">
        {{ $this->table }}
    </div>
</x-filament::page>
