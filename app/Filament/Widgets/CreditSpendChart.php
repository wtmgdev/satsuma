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
            ->usage()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as day, metered, SUM(cost_estimate) as spend')
            ->groupBy('day', 'metered')
            ->orderBy('day')
            ->get()
            ->groupBy('day')
            ->map(fn ($rows) => [
                'customer' => (float) $rows->where('metered', true)->sum('spend'),
                'internal' => (float) $rows->where('metered', false)->sum('spend'),
            ]);

        // Fill in every day so gaps read correctly.
        $labels = [];
        $customer = [];
        $internal = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('M j');
            $customer[] = $daily[$day]['customer'] ?? 0.0;
            $internal[] = $daily[$day]['internal'] ?? 0.0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Customer (USD)',
                    'data' => $customer,
                    'backgroundColor' => '#e8863c',
                    'borderColor' => '#c96a24',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Internal (USD)',
                    'data' => $internal,
                    'backgroundColor' => '#9ca3af',
                    'borderColor' => '#6b7280',
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

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true],
            ],
        ];
    }
}
