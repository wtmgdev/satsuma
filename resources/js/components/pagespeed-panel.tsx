import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Gauge, RefreshCw, Sparkles, Zap } from 'lucide-react';
import { ChevronDown } from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import { usePollWhile } from '@/hooks/use-poll-while';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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

function scoreBarColor(score: number | null): string {
    if (score === null) {
        return 'bg-muted-foreground';
    }
    if (score >= 90) {
        return 'bg-emerald-500';
    }
    if (score >= 50) {
        return 'bg-amber-500';
    }
    return 'bg-red-500';
}

function ScoreStat({ label, score }: { label: string; score: number | null }) {
    return (
        <div className="flex flex-col gap-1.5">
            <span
                className={`text-2xl font-semibold tabular-nums ${scoreColor(score)}`}
            >
                {score ?? '—'}
            </span>
            <div className="h-1 w-full overflow-hidden rounded-full bg-muted">
                {score !== null && (
                    <div
                        className={`h-full rounded-full ${scoreBarColor(score)}`}
                        style={{ width: `${score}%` }}
                    />
                )}
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
                {analysis.summary && (
                    <Collapsible>
                        <div className="flex flex-col gap-2 rounded-lg border border-border/40 bg-muted/30">
                            <CollapsibleTrigger asChild>
                                <button className="flex w-full items-center justify-between px-4 py-3 text-left">
                                    <span className="flex min-w-0 items-center gap-2">
                                        <Sparkles className="size-3.5 shrink-0 text-orange-400" />
                                        <span className="text-xs font-medium text-muted-foreground">
                                            AI summary
                                        </span>
                                        <span className="truncate text-sm font-medium">
                                            {analysis.summary.headline}
                                        </span>
                                    </span>
                                    <ChevronDown className="size-4 shrink-0 text-muted-foreground transition-transform [[data-state=open]>&]:rotate-180" />
                                </button>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <ul className="flex flex-col gap-1.5 px-4 pb-3">
                                    {analysis.summary.points.map((point) => (
                                        <li key={point} className="flex gap-2 text-sm text-muted-foreground">
                                            <span className="mt-2 size-1 shrink-0 rounded-full bg-muted-foreground" />
                                            {point}
                                        </li>
                                    ))}
                                </ul>
                            </CollapsibleContent>
                        </div>
                    </Collapsible>
                )}

                <div className="grid grid-cols-4 gap-3">
                    {CATEGORIES.map((category) => (
                        <ScoreStat
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

    usePollWhile(analyzing, ['site', 'analyses'], 5000, 36);

    // A run isn't done until both strategies have their AI summaries —
    // the analysis row lands first, the summary follows ~15s later.
    useEffect(() => {
        if (analyzing && mobile?.summary && desktop?.summary) {
            setAnalyzing(false);
        }
    }, [analyzing, mobile?.summary, desktop?.summary]);

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
