<x-filament::page>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 px-2 sm:px-3 lg:px-4">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $record->name }}
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    color="gray"
                    :href="\App\Filament\Resources\ProjectResource::getUrl('issues', ['record' => $record])"
                    tag="a"
                >
                    Visualizar em lista
                </x-filament::button>

                <x-filament::button
                    color="primary"
                    :href="\App\Filament\Resources\BoardResource::getUrl('kanban', ['record' => $this->board])"
                    tag="a"
                >
                    Abrir board
                </x-filament::button>
            </div>
        </div>

        <iframe
            src="{{ \App\Filament\Resources\BoardResource::getUrl('kanban', ['record' => $this->board]) }}"
            class="h-[80vh] w-full rounded-xl bg-transparent"
        ></iframe>
    </div>
</x-filament::page>
