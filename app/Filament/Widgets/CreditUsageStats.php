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

        // Net figures: refunds (positive rows on usage actions) net out.
        $units = fn (string $action) => (int) CreditTransaction::query()
            ->usage()
            ->where('action', $action)
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('SUM(CASE WHEN credits < 0 THEN quantity ELSE -quantity END) as units')
            ->value('units');

        $creditsUsed = -(int) CreditTransaction::query()
            ->usage()
            ->where('metered', true)
            ->where('created_at', '>=', $monthStart)
            ->sum('credits');

        $customerSpend = (float) CreditTransaction::query()
            ->usage()
            ->where('metered', true)
            ->where('created_at', '>=', $monthStart)
            ->sum('cost_estimate');

        $customerSpendPrev = (float) CreditTransaction::query()
            ->usage()
            ->where('metered', true)
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('cost_estimate');

        $internalSpend = (float) CreditTransaction::query()
            ->usage()
            ->where('metered', false)
            ->where('created_at', '>=', $monthStart)
            ->sum('cost_estimate');

        $trend = $customerSpendPrev > 0
            ? round((($customerSpend - $customerSpendPrev) / $customerSpendPrev) * 100)
            : 0;

        return [
            Stat::make('Customer credits used (MTD)', number_format($creditsUsed))
                ->description('Across all organizations')
                ->color($creditsUsed > 0 ? 'primary' : 'gray'),

            Stat::make('Customer spend (MTD)', '$'.number_format($customerSpend, 2))
                ->description($trend !== 0 ? ($trend > 0 ? "+{$trend}% vs last month" : "{$trend}% vs last month") : 'First month')
                ->descriptionIcon($trend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend > 50 ? 'warning' : 'success'),

            Stat::make('Internal spend (MTD)', '$'.number_format($internalSpend, 2))
                ->description('Unlimited orgs — usage recorded, never charged')
                ->color('gray'),

            Stat::make('PageSpeed runs / AI summaries (MTD)', $units('pagespeed_analysis').' / '.$units('ai_summary'))
                ->description('Google PSI + Berget API usage')
                ->color('info'),
        ];
    }
}
