<?php

namespace App\Filament\Widgets;

use App\Models\Scan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ScansChart extends ChartWidget
{
    protected static ?string $heading = 'Scans per Day (Last 7 Days)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $cacheKey = 'admin_scans_chart_'.now()->toDateString();

        $data = Cache::remember($cacheKey, 120, function () {
            $dateExpr = DB::getDriverName() === 'pgsql' ? 'created_at::date' : 'DATE(created_at)';

            return Scan::query()
                ->select(DB::raw("{$dateExpr} as date"), DB::raw('count(*) as count'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy(DB::raw($dateExpr))
                ->orderBy('date')
                ->get();
        });

        $labels = [];
        $values = [];

        // Fill in missing days with 0
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('M d');
            $found = $data->first(function ($row) use ($date) {
                $rowDate = $row->date instanceof \DateTimeInterface
                    ? $row->date->format('Y-m-d')
                    : substr((string) $row->date, 0, 10);

                return $rowDate === $date;
            });
            $values[] = $found ? $found->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Scans',
                    'data' => $values,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.5)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
