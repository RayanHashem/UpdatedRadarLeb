<?php

namespace App\Filament\Widgets;

use App\Models\Scan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ScansChart extends ChartWidget
{
    protected static ?string $heading = 'Scans per Day (Last 7 Days)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Scan::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        // Fill in missing days with 0
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('M d');
            $found = $data->firstWhere('date', $date);
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
