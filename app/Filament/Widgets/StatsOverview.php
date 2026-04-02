<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Scan;
use App\Models\Game;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $stats = Cache::remember("admin_stats_{$today}", 30, function () use ($today) {
            $totalUsers = User::query()->excludeAdmins()->count();

            $scansToday = Scan::whereDate('created_at', $today)->count();

            $todayRevenue = WalletTransaction::where('type', 'debit')
                ->whereDate('created_at', $today)
                ->sum('amount');

            $totalRevenue = WalletTransaction::where('type', 'debit')->sum('amount');

            $popularGameToday = Scan::whereDate('created_at', $today)
                ->select('game_id', DB::raw('count(*) as scan_count'))
                ->groupBy('game_id')
                ->orderByDesc('scan_count')
                ->first();

            $popularGameName = $popularGameToday
                ? (Game::find($popularGameToday->game_id)?->name ?? 'N/A')
                : 'No scans today';

            return compact('totalUsers', 'scansToday', 'todayRevenue', 'totalRevenue', 'popularGameName');
        });

        return [
            Stat::make('Total Users', number_format($stats['totalUsers']))
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Scans Today', number_format($stats['scansToday']))
                ->description('Scans performed today')
                ->descriptionIcon('heroicon-m-signal')
                ->color('info'),

            Stat::make('Today Revenue', '$' . number_format($stats['todayRevenue'], 2))
                ->description('Revenue from scans today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Revenue', '$' . number_format($stats['totalRevenue'], 2))
                ->description('All-time scan revenue')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Popular Game Today', $stats['popularGameName'])
                ->description('Most played game today')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}
