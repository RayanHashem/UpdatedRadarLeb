<?php

namespace App\Filament\Pages;

class MuscleCarUsers extends UsersByGamePage
{
    protected static ?string $navigationIcon  = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Muscle Car';
    protected static ?int $navigationSort     = 5;
    protected static ?string $title           = 'Muscle Car Users';

    protected function gameName(): string
    {
        return 'Muscle Car';
    }
}
