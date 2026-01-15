<?php

namespace App\Filament\Resources;

use App\Enums\SprintStatus;
use App\Enums\WorkspaceRole;
use App\Filament\Resources\SprintResource\Pages;
use App\Models\Project;
use App\Models\Sprint;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;

class SprintResource extends Resource
{
    protected static ?string $model = Sprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('project_id')
                    ->relationship(
                        name: 'project',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'workspace.workspaceUsers',
                            fn (Builder $q) => $q->where('user_id', Auth::id())->whereIn('role', [
                                WorkspaceRole::Owner->value,
                                WorkspaceRole::Admin->value,
                                WorkspaceRole::Manager->value,
                            ]),
                        ),
                    )
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                Textarea::make('goal')->rows(3)->columnSpanFull(),
                Select::make('status')
                    ->options(collect(SprintStatus::cases())->mapWithKeys(fn (SprintStatus $s) => [$s->value => $s->value])->all())
                    ->required()
                    ->disabled(fn ($record) => $record instanceof Sprint && $record->status === SprintStatus::Closed),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                TextInput::make('position')->numeric()->minValue(0)->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                TextColumn::make('position')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
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
            ])
            ->defaultSort('position');
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();
        if (! $userId) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->whereHas('project.workspace.workspaceUsers', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSprints::route('/'),
            'create' => Pages\CreateSprint::route('/create'),
            'edit' => Pages\EditSprint::route('/{record}/edit'),
        ];
    }
}
