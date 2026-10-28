import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Gauge, RefreshCw, Zap } from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import { usePollWhile } from '@/hooks/use-poll-while';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import type { SiteAnalysis } from '@/types';

const CATEGORIES: { key: string; label: string }[] = [
    { key: 'performance', label: 'Performance' },
    { key: 'accessibility', label: 'Accessibility' },
    { key: 'best-practices', label: 'Best Practices' },
    { key: 'seo', label: 'SEO' },
];

function scoreColor(score: number | null): string {
    if (score === null) {
        return 'text-muted-foreground';
    }
    if (score >= 90) {
        return 'text-emerald-500';
    }
    if (score >= 50) {
        return 'text-amber-500';
    }
    return 'text-red-500';
}

function ScoreRing({ label, score }: { label: string; score: number | null }) {
    const radius = 26;
    const circumference = 2 * Math.PI * radius;
    const progress = ((score ?? 0) / 100) * circumference;

    return (
        <div className="flex flex-col items-center gap-1.5">
            <div className="relative size-16">
                <svg viewBox="0 0 64 64" className="size-16 -rotate-90">
                    <circle
                        cx="32"
                        cy="32"
                        r={radius}
                        fill="none"
                        strokeWidth="5"
                        className="stroke-muted"
                    />
                    {score !== null && (
                        <circle
                            cx="32"
                            cy="32"
                            r={radius}
                            fill="none"
                            strokeWidth="5"
                            strokeLinecap="round"
                            strokeDasharray={circumference}
                            strokeDashoffset={circumference - progress}
                            className={scoreColor(score).replace('text-', 'stroke-')}
                        />
                    )}
                </svg>
                <span
                    className={`absolute inset-0 flex items-center justify-center text-lg font-semibold tabular-nums ${scoreColor(score)}`}
                >
                    {score ?? '—'}
                </span>
            </div>
            <span className="text-xs text-muted-foreground">{label}</span>
        </div>
    );
}

function StrategyCard({
    title,
    analysis,
}: {
    title: string;
    analysis: SiteAnalysis | null | undefined;
}) {
    if (!analysis?.data) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">{title}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Gauge />
                            </EmptyMedia>
                            <EmptyTitle>No analysis yet</EmptyTitle>
                            <EmptyDescription>
                                Run an analysis to get Lighthouse scores for
                                this strategy.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </CardContent>
            </Card>
        );
    }

    const data = analysis.data;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
                <CardDescription>
                    Analyzed {new Date(analysis.created_at).toLocaleString()}
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-5">
                <div className="grid grid-cols-4 gap-2">
                    {CATEGORIES.map((category) => (
                        <ScoreRing
                            key={category.key}
                            label={category.label}
                            score={data.scores[category.key] ?? null}
                        />
                    ))}
                </div>

                <div className="flex flex-col divide-y divide-border/40">
                    {data.metrics.map((metric) => (
                        <div
                            key={metric.id}
                            className="flex items-center justify-between py-1.5 text-sm"
                        >
                            <span className="text-muted-foreground">
                                {metric.label}
                            </span>
                            <span
                                className={`font-medium tabular-nums ${metric.score !== null ? scoreColor(metric.score * 100) : ''}`}
                            >
                                {metric.value}
                            </span>
                        </div>
                    ))}
                </div>

                {data.opportunities.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <span className="text-xs font-medium text-muted-foreground">
                            Top opportunities
                        </span>
                        {data.opportunities.map((opportunity) => (
                            <div
                                key={opportunity.title}
                                className="flex items-center justify-between gap-3 text-sm"
                            >
                                <span className="flex min-w-0 items-center gap-2">
                                    <Zap className="size-3.5 shrink-0 text-amber-500" />
                                    <span className="truncate">{opportunity.title}</span>
                                </span>
                                <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                    {opportunity.savings}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export function PageSpeedPanel({
    siteId,
    mobile,
    desktop,
}: {
    siteId: number;
    mobile: SiteAnalysis | null | undefined;
    desktop: SiteAnalysis | null | undefined;
}) {
    const [analyzing, setAnalyzing] = useState(false);

    usePollWhile(analyzing, ['site'], 5000, 36);

    useEffect(() => {
        if (!analyzing) {
            return;
        }
        const timer = window.setTimeout(() => setAnalyzing(false), 200000);

        return () => window.clearTimeout(timer);
    }, [analyzing]);

    const run = () => {
        setAnalyzing(true);
        router.post(
            SiteController.runPageSpeed(siteId).url,
            {},
            {
                preserveScroll: true,
                onError: () => setAnalyzing(false),
            },
        );
        toast.info('PageSpeed analysis queued — this takes a minute or two.');
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between">
                <div>
                    <CardTitle>Performance</CardTitle>
                    <CardDescription>
                        Lighthouse audits via Google PageSpeed — refreshed on
                        demand, cached for a day.
                    </CardDescription>
                </div>
                <Button size="sm" variant="outline" onClick={run} disabled={analyzing}>
                    {analyzing ? (
                        <Spinner data-icon="inline-start" />
                    ) : (
                        <RefreshCw data-icon="inline-start" />
                    )}
                    {analyzing ? 'Analyzing…' : 'Run analysis'}
                </Button>
            </CardHeader>
            <CardContent className="grid gap-4 lg:grid-cols-2">
                <StrategyCard title="Mobile" analysis={mobile} />
                <StrategyCard title="Desktop" analysis={desktop} />
            </CardContent>
        </Card>
    );
}
