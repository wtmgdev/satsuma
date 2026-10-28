<?php

namespace App\Filament\Widgets;

use App\Models\CreditTransaction;
use Filament\Widgets\ChartWidget;

class CreditSpendChart extends ChartWidget
{
    protected ?string $heading = 'Estimated external spend — last 30 days';

    protected string $color = 'warning';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $daily = CreditTransaction::query()
            ->where('credits', '<', 0)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as day, SUM(cost_estimate) as spend')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('spend', 'day');

        // Fill in every day so gaps read correctly.
        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('M j');
            $values[] = (float) ($daily[$day] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Estimated spend (USD)',
                    'data' => $values,
                    'backgroundColor' => '#e8863c',
                    'borderColor' => '#c96a24',
                    'fill' => true,
                    'tension' => 0.3,
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
