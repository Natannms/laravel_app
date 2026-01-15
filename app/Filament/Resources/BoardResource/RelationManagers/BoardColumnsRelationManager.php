<?php

namespace App\Filament\Resources\BoardResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class BoardColumnsRelationManager extends RelationManager
{
    protected static string $relationship = 'columns';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('position')->numeric()->minValue(0)->default(0),
                TextInput::make('wip_limit')->numeric()->minValue(0),
                ColorPicker::make('color'),
                Toggle::make('is_done'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('position')->sortable(),
                TextColumn::make('wip_limit')->sortable(),
                TextColumn::make('is_done')->badge()->formatStateUsing(fn (bool $state) => $state ? 'DONE' : '—'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}

