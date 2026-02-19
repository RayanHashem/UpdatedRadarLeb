<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrawResource\Pages;
use App\Models\Draw;
use Filament\Forms;
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
                Tables\Columns\BadgeColumn::make('status')
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
                    ]),
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
