<?php

namespace App\Filament\Pages;

class BikeElectronicsUsers extends UsersByGamePage
{
    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Bike & Electronics';
    protected static ?int $navigationSort     = 3;
    protected static ?string $title           = 'Bike & Electronics Users';

    protected function gameName(): string
    {
        return 'Bike & Electronics';
    }
}
