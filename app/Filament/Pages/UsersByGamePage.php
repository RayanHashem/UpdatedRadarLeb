<?php

namespace App\Filament\Pages;

use App\Models\Game;
use App\Models\Scan;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract base for the "Users who have played game X" admin pages.
 *
 * The five concrete pages (Mobile / Bike & Electronics / SUV / Muscle Car /
 * Super Car) used to be near-identical 60-line copy-pastes. Each one
 * only varies by:
 *   1. The game name to filter on.
 *   2. The navigation label / icon / sort order / title.
 *
 * Subclasses declare those via the `gameName()` method and the static
 * navigation properties. Everything else (column set, query builder, empty
 * state, table actions) is shared here.
 *
 * Each row represents a single SCAN (one tap of the SCAN button) for the
 * given prize. The same user appears once per scan they've made on this
 * prize — duplicates are intentional, so admins can audit the exact
 * sequence of "who played, when". The "Played at" column is the scan's
 * own `created_at`. Wallet balance / spent / current game / draw # are
 * pulled from the user record at view time so the row reflects the user's
 * present-day state, not a snapshot.
 */
abstract class UsersByGamePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Users';

    protected static string $view = 'filament.pages.users-by-game';

    /**
     * The exact `games.name` to filter by. Subclasses MUST override.
     *
     * Returning a name that doesn't match a games row makes the table
     * empty (graceful) but the navigation entry still appears — fail
     * loud during dev to catch typos.
     */
    abstract protected function gameName(): string;

    public function table(Table $table): Table
    {
        $gameId = Game::query()
            ->where('name', $this->gameName())
            ->value('id');

        // Sentinel value (0) means "no game found by that name" — the query
        // will return zero rows rather than the full scans list. Avoids
        // accidentally rendering everything on a typo in gameName().
        $effectiveGameId = $gameId ?? 0;

        $query = Scan::query()
            ->where('scans.game_id', $effectiveGameId)
            ->whereHas('user', fn (Builder $q) => $q->excludeAdmins())
            ->with([
                'user' => fn ($q) => $q->withSum(
                    ['walletTransactions as radar_cash_spent' => fn ($qq) => $qq->where('type', 'debit')],
                    'amount'
                ),
                'user.game',
            ]);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('user.id')
                    ->label('ID')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone_number')
                    ->label('Phone nb')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('user.game.name')
                    ->label('Current Game')
                    ->formatStateUsing(fn ($state) => $state ?? '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.wallet_balance')
                    ->label('Radar cash balance')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.radar_cash_spent')
                    ->label('Radar cash spent')
                    ->money('usd')
                    ->default(0)
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('user.game.draw_number')
                    ->label('Draw #')
                    ->formatStateUsing(fn ($state) => $state ?? '')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Played at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyState(view('filament.tables.empty-state-with-headers', [
                'headings' => ['ID', 'Name', 'Phone nb', 'Current Game', 'Radar cash balance', 'Radar cash spent', 'Draw #', 'Played at'],
                'message' => 'No scans for this prize yet',
            ]));
    }
}
