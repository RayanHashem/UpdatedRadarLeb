<?php

namespace App\Filament\Pages;

class SuperCashPrizeUsers extends UsersByGamePage
{
    protected static ?string $navigationIcon  = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Super Cash';
    protected static ?int $navigationSort     = 6;
    protected static ?string $title           = 'Super Cash Prize Users';

    protected function gameName(): string
    {
        return 'Super Cash Prize';
    }
}
