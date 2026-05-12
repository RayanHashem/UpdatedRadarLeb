<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Game;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract base for the "Users who have spent on game X" admin pages.
 *
 * The five concrete pages (Mobile / Bike & Electronics / SUV / Muscle Car /
 * Super Car) used to be near-identical 60-line copy-pastes. Each one
 * only varied by:
 *   1. The game name to filter on.
 *   2. The navigation label / icon / sort order / title.
 *
 * Subclasses declare those via the `gameName()` method and the static
 * navigation properties. Everything else (column set, query builder, empty
 * state, table actions) is shared here.
 *
 * Filtering is by `wallet_transactions.game_id` — the source of truth for
 * "what has the user actually spent on" — not by `users.game_id` (which is
 * just the user's currently-selected prize). See docs/history.md for the
 * multi-game-spending refactor that introduced this.
 */
abstract class UsersByGamePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Users';
    protected static string $view             = 'filament.pages.users-by-game';

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

        $query = User::query()
            ->excludeAdmins()
            ->withRadarCashSpent()
            ->withDistinctGameCount();

        if ($gameId !== null) {
            $query->whereHas('walletTransactions', function (Builder $q) use ($gameId) {
                $q->where('game_id', $gameId)
                    ->whereIn('type', ['debit', 'play', 'spend']);
            });
        } else {
            // No matching game row — return zero results rather than the full
            // user list. Avoids accidentally rendering all users on a typo.
            $query->whereRaw('1 = 0');
        }

        return $table
            ->query($query)
            ->columns(UserResource::getTableColumns())
            ->filters([])
            ->actions(UserResource::getTableActions())
            ->bulkActions([])
            ->emptyState(view('filament.tables.empty-state-with-headers', [
                'headings' => ['ID', 'Name', 'Phone nb', 'Prize', 'Radar cash balance', 'Radar cash spent', 'Draw #', 'Created at'],
                'message'  => 'No users yet',
            ]));
    }
}
