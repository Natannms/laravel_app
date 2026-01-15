<?php

namespace App\Filament\Resources\IssueResource\RelationManagers;

use App\Models\IssueAttachment;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class IssueAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file_path')
                    ->label('Arquivo')
                    ->disk('public')
                    ->directory(fn () => 'issues/' . $this->getOwnerRecord()->issue_key)
                    ->preserveFilenames()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')->label('Arquivo')->searchable(),
                TextColumn::make('mime_type')->label('MIME'),
                TextColumn::make('file_size')->label('Tamanho')->formatStateUsing(fn ($state) => $state ? number_format(((int) $state) / 1024, 1) . ' KB' : '—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $path = (string) ($data['file_path'] ?? '');
                        $fullPath = $path !== '' ? Storage::disk('public')->path($path) : null;
                        $data['user_id'] = Auth::id();
                        $data['file_name'] = $path !== '' ? basename($path) : 'file';
                        $data['mime_type'] = $fullPath ? File::mimeType($fullPath) : null;
                        $data['file_size'] = $fullPath ? File::size($fullPath) : null;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Abrir')
                    ->url(fn (IssueAttachment $record) => $record->url)
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ]);
    }
}
