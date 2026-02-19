<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Game;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'All Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Prize & Wallet')
                    ->schema([
                Forms\Components\Select::make('game_id')
                    ->label('Current Game')
                    ->options(Game::pluck('name', 'id'))
                    ->disabled()
                    ->helperText('User\'s currently selected game. See wallet transactions for spending history across games.'),
                        Forms\Components\TextInput::make('wallet_balance')
                            ->label('Radar cash balance')
                            ->disabled()
                            ->prefix('$'),
                    ])->columns(2),
            ]);
    }

    /** Shared table columns for All Users and per-prize pages (ID, Name, Phone, Prize, Balance, Spent, Draw #, Created at). */
    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->visibleFrom('md'),
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('phone_number')
                ->label('Phone nb')
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),
            Tables\Columns\TextColumn::make('game.name')
                ->label('Current Game')
                ->formatStateUsing(fn ($state) => $state ?? '—')
                ->sortable()
                ->description(function (User $record): string {
                    // Check if game_id column exists (defensive for pre-migration state)
                    if (!Schema::hasColumn('wallet_transactions', 'game_id')) {
                        return '';
                    }
                    try {
                        return $record->walletTransactions()
                            ->whereNotNull('game_id')
                            ->whereIn('type', ['debit', 'play', 'spend'])
                            ->distinct('game_id')
                            ->count() > 1
                            ? 'Plays multiple games'
                            : '';
                    } catch (\Exception $e) {
                        return '';
                    }
                }),
            Tables\Columns\TextColumn::make('wallet_balance')
                ->label('Radar cash balance')
                ->money('usd')
                ->sortable(),
            Tables\Columns\TextColumn::make('radar_cash_spent')
                ->label('Radar cash spent')
                ->money('usd')
                ->default(0)
                ->visibleFrom('lg'),
            Tables\Columns\TextColumn::make('game.draw_number')
                ->label('Draw #')
                ->formatStateUsing(fn ($state) => $state ?? '')
                ->sortable()
                ->visibleFrom('md'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created at')
                ->dateTime()
                ->sortable()
                ->visibleFrom('lg'),
        ];
    }

    /** Shared table actions: View + Topoff Radar Cash (no Edit). */
    public static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
            Tables\Actions\Action::make('topoffRadarCash')
                ->label('Top up')
                ->icon('heroicon-m-plus-circle')
                ->color('success')
                ->modalWidth('sm')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount')
                        ->placeholder('e.g. 10 or 25.50')
                        ->prefix('$')
                        ->helperText('Credits the user\'s Radar Cash balance.')
                        ->inputMode('decimal')
                        ->extraInputAttributes(['type' => 'text'])
                        ->required()
                        ->rules(['regex:/^\d+(\.\d{1,2})?$/', 'min:0.01'])
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('Confirm')
                ->action(function (array $data, User $record): void {
                    $raw = trim((string) ($data['amount'] ?? ''));
                    $normalized = str_replace(',', '.', $raw);
                    $amount = (float) $normalized;
                    if ($amount < 0.01) {
                        return;
                    }
                    DB::transaction(function () use ($record, $amount) {
                        $record->increment('wallet_balance', $amount);
                        $record->refresh();
                        WalletTransaction::create([
                            'user_id'       => $record->id,
                            'type'          => 'topup',
                            'amount'        => $amount,
                            'balance_after' => $record->wallet_balance,
                            'notes'         => 'Admin topoff',
                        ]);
                    });
                })
                ->successNotificationTitle('Balance updated.'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->label('Prize')
                    ->options(Game::pluck('name', 'id')),
            ])
            ->actions(self::getTableActions())
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view'  => Pages\ViewUser::route('/{record}'),
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
