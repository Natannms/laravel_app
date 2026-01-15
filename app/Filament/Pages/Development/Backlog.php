<?php

namespace App\Filament\Pages\Development;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\Issue;
use App\Models\Sprint;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class Backlog extends BaseDevelopmentPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Backlog';

    protected static ?string $slug = 'development/backlog';

    protected static string $view = 'filament.pages.development.backlog';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->queryBacklog())
            ->columns([
                TextColumn::make('issue_key')->label('Key')->sortable()->searchable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('title')->sortable()->searchable()->limit(70),
                TextColumn::make('estimate_hours')
                    ->label('Est.')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, Issue $record): string {
                        $hours = $state === null ? '' : (rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.') . 'h');
                        $coffee = ((int) ($record->coffee_breaks ?? 0)) > 0 ? ' ☕' : '';

                        return $hours . $coffee;
                    }),
                TextColumn::make('assignee.name')->label('Assignee')->sortable(),
                TextColumn::make('blocked')->badge()->formatStateUsing(fn (bool $state) => $state ? 'BLOCKED' : 'OK'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(IssueType::cases())->mapWithKeys(fn (IssueType $t) => [$t->value => $t->value])->all()),
                TernaryFilter::make('blocked')->label('Blocked'),
            ])
            ->actions([
                Tables\Actions\Action::make('estimate')
                    ->label('Estimativa')
                    ->form([
                        Select::make('estimate_hours')
                            ->label('Estimativa (h)')
                            ->options([
                                '' => '—',
                                1 => '1',
                                2 => '2',
                                4 => '4',
                                6 => '6',
                                8 => '8',
                            ])
                            ->native(false),
                        Toggle::make('coffee')
                            ->label('☕'),
                    ])
                    ->fillForm(function (Issue $record): array {
                        return [
                            'estimate_hours' => $record->estimate_hours === null ? '' : (string) ((float) $record->estimate_hours),
                            'coffee' => ((int) ($record->coffee_breaks ?? 0)) > 0,
                        ];
                    })
                    ->action(function (Issue $record, array $data) {
                        $estimate = $data['estimate_hours'] ?? '';
                        $record->estimate_hours = $estimate === '' ? null : (float) $estimate;
                        $record->coffee_breaks = ! empty($data['coffee']) ? 1 : 0;
                        $record->save();
                    }),
                Tables\Actions\Action::make('block')
                    ->label('Bloquear')
                    ->visible(fn (Issue $record) => ! $record->blocked)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('blocked_reason')->label('Motivo')->required()->rows(3),
                    ])
                    ->action(function (Issue $record, array $data) {
                        $record->blocked = true;
                        $record->blocked_reason = $data['blocked_reason'] ?? null;
                        $record->save();

                        Notification::make()->title('Issue bloqueada')->success()->send();
                    }),
                Tables\Actions\Action::make('unblock')
                    ->label('Desbloquear')
                    ->visible(fn (Issue $record) => (bool) $record->blocked)
                    ->action(function (Issue $record) {
                        $record->blocked = false;
                        $record->blocked_reason = null;
                        $record->blocked_by_issue_id = null;
                        $record->save();

                        Notification::make()->title('Issue desbloqueada')->success()->send();
                    }),
                Tables\Actions\Action::make('move_to_sprint')
                    ->label('Mover para sprint')
                    ->form([
                        Select::make('sprint_id')
                            ->label('Sprint')
                            ->options(fn () => Sprint::query()
                                ->where('project_id', $this->project->id)
                                ->where('status', '!=', SprintStatus::Closed->value)
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Issue $record, array $data) {
                        $record->sprint_id = $data['sprint_id'];
                        $record->save();
                    }),
                Tables\Actions\EditAction::make()
                    ->url(fn (Issue $record) => route('filament.admin.resources.issues.edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function queryBacklog(): Builder
    {
        return Issue::query()
            ->where('project_id', $this->project->id)
            ->whereNull('sprint_id')
            ->with(['assignee']);
    }
}
