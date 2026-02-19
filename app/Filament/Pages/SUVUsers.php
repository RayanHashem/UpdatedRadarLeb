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

class SUVUsers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'SUV';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.users-by-game';

    protected static ?string $title = 'SUV Users';

    public function table(Table $table): Table
    {
        $gameId = Game::where('name', 'SUV')->first()?->id;

        $hasGameIdColumn = Schema::hasColumn('wallet_transactions', 'game_id');

        $query = User::query()
            ->excludeAdmins()
            ->withRadarCashSpent();

        if ($hasGameIdColumn) {
            $query->whereHas('walletTransactions', function ($q) use ($gameId) {
                $q->where('game_id', $gameId)
                    ->whereIn('type', ['debit', 'play', 'spend']);
            });
        } else {
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
