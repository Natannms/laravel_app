<?php

namespace App\Filament\Resources\IssueResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class IssueActivityRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest('created_at'))
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Usuário')->sortable(),
                TextColumn::make('action')->label('Ação')->sortable(),
                TextColumn::make('before')->label('Antes')->formatStateUsing(fn ($state) => $state ? json_encode($state) : '—')->wrap(),
                TextColumn::make('after')->label('Depois')->formatStateUsing(fn ($state) => $state ? json_encode($state) : '—')->wrap(),
            ])
            ->actions([])
            ->headerActions([])
            ->paginated([25, 50, 100]);
    }
}

