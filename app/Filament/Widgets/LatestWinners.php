<?php

namespace App\Filament\Widgets;

use App\Models\Winner;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestWinners extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Winners';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Winner::query()
                    ->with(['winner', 'game'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#'),
                Tables\Columns\TextColumn::make('game.name')
                    ->label('Game'),
                Tables\Columns\TextColumn::make('draw_number')
                    ->label('Draw #'),
                Tables\Columns\TextColumn::make('winner.name')
                    ->label('Winner')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('winner.phone_number')
                    ->label('Phone')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('won_at')
                    ->label('Won At')
                    ->dateTime()
                    ->default('-'),
            ])
            ->paginated(false);
    }
}
