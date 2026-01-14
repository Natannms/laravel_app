<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use App\Models\Project;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function handleRecordCreation(array $data): Project
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return Project::query()->create($data);
            } catch (QueryException $e) {
                $isUniqueViolation = $e->getCode() === '23000';
                if (! $isUniqueViolation) {
                    throw $e;
                }
            }
        }

        return Project::query()->create($data);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Projeto criado')
            ->body("Key gerada: {$this->record->key}")
            ->success()
            ->send();
    }
}
