<?php

namespace App\Filament\Resources\BoardResource\Pages;

use App\Filament\Resources\BoardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoard extends EditRecord
{
    protected static string $resource = BoardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kanban')
                ->label('Kanban')
                ->url(fn () => static::getResource()::getUrl('kanban', ['record' => $this->record])),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}

