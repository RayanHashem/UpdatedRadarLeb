<?php

namespace App\Filament\Pages;

class SUVUsers extends UsersByGamePage
{
    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'SUV';
    protected static ?int $navigationSort     = 4;
    protected static ?string $title           = 'SUV Users';

    protected function gameName(): string
    {
        return 'SUV';
    }
}
