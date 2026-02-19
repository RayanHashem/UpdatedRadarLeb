<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Models\Game;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Schema;

class MobileUsers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'Mobile';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.users-by-game';

    protected static ?string $title = 'Mobile Users';

    public function table(Table $table): Table
    {
        $gameId = Game::where('name', 'Mobile')->first()?->id;

        // Fallback to old behavior (users.game_id) if wallet_transactions.game_id doesn't exist yet
        $hasGameIdColumn = Schema::hasColumn('wallet_transactions', 'game_id');

        $query = User::query()
            ->excludeAdmins()
            ->withRadarCashSpent();

        if ($hasGameIdColumn) {
            // New: filter by wallet_transactions.game_id
            $query->whereHas('walletTransactions', function ($q) use ($gameId) {
                $q->where('game_id', $gameId)
                    ->whereIn('type', ['debit', 'play', 'spend']);
            });
        } else {
            // Fallback: filter by users.game_id (old behavior)
            $query->whereNotNull('game_id')
                ->where('game_id', $gameId);
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
