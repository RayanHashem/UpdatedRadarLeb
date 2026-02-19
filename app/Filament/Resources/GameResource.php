<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Prizes';

    protected static ?string $modelLabel = 'Prize';

    protected static ?string $pluralModelLabel = 'Prizes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Prize Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('target_amount')
                            ->label('Target amount ($)')
                            ->numeric()
                            ->disabled()
                            ->prefix('$'),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('price_to_play')
                            ->label('Price to Play ($)')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                        Forms\Components\TextInput::make('minimum_deposit')
                            ->label('Minimum Deposit ($)')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                        Forms\Components\TextInput::make('draw_number')
                            ->label('Current Draw Number')
                            ->required()
                            ->numeric(),
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Prize')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_amount')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format((float) $state, 0) : '—')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('price_to_play')
                    ->label('Price to Play')
                    ->money('usd')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('draw_number')
                    ->label('Draw #')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Enabled'),
                Tables\Columns\TextColumn::make('minimum_winning')
                    ->label('Minimum amount for winning')
                    ->getStateUsing(fn (Game $record) => $record->target_amount)
                    ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format((float) $state, 0) : '—')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('target_amount', $direction);
                    })
                    ->visibleFrom('lg'),
                Tables\Columns\ViewColumn::make('prize_progress')
                    ->label('Prize progress')
                    ->view('filament.tables.columns.prize-progress')
                    ->visibleFrom('md'),
            ])
            ->defaultSort('id')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled Status')
                    ->boolean()
                    ->trueLabel('Enabled Only')
                    ->falseLabel('Disabled Only')
                    ->placeholder('All Prizes'),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyState(view('filament.tables.empty-state-with-headers', [
                'headings' => ['Prize', 'Price', 'Price to Play', 'Draw #', 'Enabled', 'Minimum amount for winning', 'Prize progress'],
                'message'  => 'No prizes available yet',
            ]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
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

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
