<?php

namespace App\Filament\Pages;

class MobileUsers extends UsersByGamePage
{
    protected static ?string $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Mobile';
    protected static ?int $navigationSort     = 2;
    protected static ?string $title           = 'Mobile Users';

    protected function gameName(): string
    {
        return 'Mobile';
    }
}
