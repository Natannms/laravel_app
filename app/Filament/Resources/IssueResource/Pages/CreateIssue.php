<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use App\Models\Issue;
use App\Models\Project;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;

    protected function handleRecordCreation(array $data): Issue
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    Project::query()->whereKey($data['project_id'])->lockForUpdate()->firstOrFail();

                    return Issue::query()->create($data);
                });
            } catch (QueryException $e) {
                $isUniqueViolation = $e->getCode() === '23000';
                if (! $isUniqueViolation) {
                    throw $e;
                }
            }
        }

        return Issue::query()->create($data);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Issue criada')
            ->body("Key: {$this->record->issue_key}")
            ->success()
            ->send();
    }
}

