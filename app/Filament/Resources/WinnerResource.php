<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WinnerResource\Pages;
use App\Models\Winner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WinnerResource extends Resource
{
    protected static ?string $model = Winner::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Winners';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('game_name')
                    ->disabled(),
                Forms\Components\TextInput::make('user_name')
                    ->disabled(),
                Forms\Components\TextInput::make('draw_number')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('won_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('winner.name')
                    ->label('Name')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('winner.phone_number')
                    ->label('Phone number')
                    ->formatStateUsing(fn ($state) => $state !== null ? (string) $state : '—')
                    ->searchable()
                    ->placeholder('—')
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('won_at')
                    ->label('Date & Time')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('game.name')
                    ->label('Prize won')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('draw_number')
                    ->label('Draw #')
                    ->sortable()
                    ->placeholder('—')
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->label('Prize')
                    ->relationship('game', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('won_at', 'desc')
            ->emptyState(view('filament.tables.empty-state-with-headers', [
                'headings' => ['ID', 'Name', 'Phone number', 'Date & Time', 'Prize won', 'Draw #'],
                'message'  => 'No winners yet',
            ]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWinners::route('/'),
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
