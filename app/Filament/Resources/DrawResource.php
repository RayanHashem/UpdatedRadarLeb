<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrawResource\Pages;
use App\Models\Draw;
// Draw is referenced inside a column closure for ->getStateUsing() — make
// sure the import is present even when the closure is the only usage.

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DrawResource extends Resource
{
    protected static ?string $model = Draw::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?string $navigationLabel = 'Draws';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('game.name')
                    ->label('Game')
                    ->sortable(),
                Tables\Columns\TextColumn::make('draw_number')
                    ->label('Draw #')
                    ->sortable(),
                /*
                 * Status is derived, not the stored `draws.status` column.
                 *
                 *   - If the draw already has a winner, it is "closed"
                 *     forever — that draw round is done.
                 *   - Otherwise, status mirrors the parent game's
                 *     `is_enabled` toggle: enabling the prize "opens"
                 *     gameplay for the current draw; disabling it
                 *     "closes" play without yet declaring a winner.
                 *
                 * This way the badge reacts in real time to the
                 * `is_enabled` toggle on the Prizes page.
                 */
                Tables\Columns\BadgeColumn::make('status')
                    ->getStateUsing(function (Draw $record): string {
                        if ($record->winner_user_id !== null) {
                            return 'closed';
                        }

                        return ($record->game?->is_enabled) ? 'open' : 'closed';
                    })
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('winner.name')
                    ->label('Winner')
                    ->default('-')
                    ->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('winner.phone_number')
                    ->label('Winner Phone')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('opened_at')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || $state === '-') {
                            return '—';
                        }
                        try {
                            return \Carbon\Carbon::parse($state)->format('M j, Y g:i A');
                        } catch (\Throwable) {
                            return '—';
                        }
                    })
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('closed_at')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || $state === '-') {
                            return '—';
                        }
                        try {
                            return \Carbon\Carbon::parse($state)->format('M j, Y g:i A');
                        } catch (\Throwable) {
                            return '—';
                        }
                    })
                    ->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->label('Game')
                    ->relationship('game', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    /*
                     * Match the derived status logic in the column above:
                     * a draw is "open" only when there is no winner yet
                     * AND the parent game is currently enabled.
                     */
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'open') {
                            $query->whereNull('winner_user_id')
                                ->whereHas('game', fn ($q) => $q->where('is_enabled', true));
                        } elseif ($value === 'closed') {
                            $query->where(function ($q) {
                                $q->whereNotNull('winner_user_id')
                                    ->orWhereHas('game', fn ($g) => $g->where('is_enabled', false));
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDraws::route('/'),
        ];
    }

    /**
     * Eager-load `game` and `winner` so the table columns
     * `game.name`, `winner.name`, `winner.phone_number` don't fire one
     * SELECT per row.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['game', 'winner']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
