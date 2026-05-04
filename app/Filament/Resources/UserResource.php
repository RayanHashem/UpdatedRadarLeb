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
                    return ($record->distinct_game_count ?? 0) > 1
                        ? 'Plays multiple games'
                        : '';
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
                    // Normalise comma decimals (e.g. "12,50") and reject
                    // anything below the 1¢ floor.
                    $amount = (float) str_replace(',', '.', trim((string) ($data['amount'] ?? '')));
                    if ($amount < 0.01) {
                        return;
                    }

                    // Delegate to the action — same atomic credit logic any
                    // future code path (vouchers, scheduled bonuses, etc.)
                    // can reuse without copy-pasting the DB::transaction.
                    app(\App\Actions\Wallet\TopOffWallet::class)($record, $amount, 'Admin topoff');
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

    /**
     * Eager-load + scope-apply for the table query.
     *
     * Four things happen here, several of which were broken before:
     *   1. `with(['game'])` — kills the per-row N+1 on the `Current Game`
     *      and `Draw #` columns. ~25 rows × 2 columns = ~50 queries → 1.
     *   2. `withRadarCashSpent()` — adds the `radar_cash_spent` aggregate
     *      column the table renders. Without it the column was silently
     *      rendering 0 for every user.
     *   3. `withDistinctGameCount()` — drives the "Plays multiple games"
     *      hint under the Current Game column. Without this scope the
     *      hint never showed.
     *   4. `excludeAdmins()` — admin accounts are operational; they don't
     *      belong in a list of customers. Matches the per-game pages.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->excludeAdmins()
            ->with(['game'])
            ->withRadarCashSpent()
            ->withDistinctGameCount();
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
