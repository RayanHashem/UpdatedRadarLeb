<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Scan;
use App\Models\Game;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();

        // Total users (exclude admin accounts)
        $totalUsers = User::query()->excludeAdmins()->count();

        // Total scans today
        $scansToday = Scan::whereDate('created_at', $today)->count();

        // Today revenue (sum of debits)
        $todayRevenue = WalletTransaction::where('type', 'debit')
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Total revenue (all time debits)
        $totalRevenue = WalletTransaction::where('type', 'debit')->sum('amount');

        // Most popular game today
        $popularGameToday = Scan::whereDate('created_at', $today)
            ->select('game_id', DB::raw('count(*) as scan_count'))
            ->groupBy('game_id')
            ->orderByDesc('scan_count')
            ->first();

        $popularGameName = $popularGameToday 
            ? Game::find($popularGameToday->game_id)?->name ?? 'N/A'
            : 'No scans today';

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Scans Today', number_format($scansToday))
                ->description('Scans performed today')
                ->descriptionIcon('heroicon-m-signal')
                ->color('info'),

            Stat::make('Today Revenue', '$' . number_format($todayRevenue, 2))
                ->description('Revenue from scans today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('All-time scan revenue')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Popular Game Today', $popularGameName)
                ->description('Most played game today')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}
