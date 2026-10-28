<?php

namespace App\Filament\Widgets;

use App\Models\CreditTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditUsageStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $spend = CreditTransaction::query()
            ->where('credits', '<', 0)
            ->where('created_at', '>=', $monthStart);

        $creditsUsed = -$spend->clone()->sum('credits');
        $estimatedSpend = (float) $spend->clone()->sum('cost_estimate');
        $estimatedSpendPrev = (float) CreditTransaction::query()
            ->where('credits', '<', 0)
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('cost_estimate');

        $trend = $estimatedSpendPrev > 0
            ? round((($estimatedSpend - $estimatedSpendPrev) / $estimatedSpendPrev) * 100)
            : 0;

        return [
            Stat::make('Credits used this month', number_format($creditsUsed))
                ->description('Across all organizations')
                ->color($creditsUsed > 0 ? 'primary' : 'gray'),

            Stat::make('Estimated spend (MTD)', '$'.number_format($estimatedSpend, 2))
                ->description($trend !== 0 ? ($trend > 0 ? "+{$trend}% vs last month" : "{$trend}% vs last month") : 'First month')
                ->descriptionIcon($trend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend > 50 ? 'warning' : 'success'),

            Stat::make('AI summaries this month', CreditTransaction::query()
                ->where('action', 'ai_summary')
                ->where('created_at', '>=', $monthStart)
                ->sum('quantity'))
                ->description('Berget API usage')
                ->color('info'),

            Stat::make('PageSpeed runs this month', CreditTransaction::query()
                ->where('action', 'pagespeed_analysis')
                ->where('created_at', '>=', $monthStart)
                ->sum('quantity'))
                ->description('Google PSI API usage')
                ->color('info'),
        ];
    }
}
