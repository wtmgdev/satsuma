import { Head, Link } from '@inertiajs/react';
import { BarChart3, Gauge, Sparkles, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Credits = {
    balance: number;
    allowance: number;
    unlimited: boolean;
    resetsAt: string | null;
};

type ActionUsage = {
    action: string;
    units: number;
    credits: number;
    cost: number;
};

type Transaction = {
    id: number;
    action: string;
    credits: number;
    quantity: number;
    cost_estimate: string | null;
    description: string | null;
    created_at: string;
    user?: { email: string } | null;
};

const ACTION_LABELS: Record<string, string> = {
    pagespeed_analysis: 'PageSpeed analysis',
    ai_summary: 'AI summary',
    crawl_page: 'Crawl page',
    topup: 'Top-up',
    grant: 'Bonus grant',
    monthly_reset: 'Monthly reset',
};

const ACTION_ICONS: Record<string, typeof Gauge> = {
    pagespeed_analysis: Gauge,
    ai_summary: Sparkles,
    crawl_page: BarChart3,
};

const money = (value: number) =>
    value >= 1 ? `$${value.toFixed(2)}` : `$${value.toFixed(4)}`;

export default function Usage({
    credits,
    usedThisMonth,
    estimatedCost,
    byAction,
    transactions,
}: {
    credits: Credits;
    usedThisMonth: number;
    estimatedCost: number;
    byAction: ActionUsage[];
    transactions: Transaction[];
}) {
    const resets = credits.resetsAt
        ? new Date(credits.resetsAt).toLocaleDateString(undefined, {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : null;

    return (
        <>
            <Head title="Usage" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">Usage</h1>
                    <p className="text-sm text-muted-foreground">
                        Your organization's credit spend and activity
                    </p>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div>
                            <CardTitle>Credits remaining</CardTitle>
                            <CardDescription>
                                {credits.unlimited
                                    ? 'Unlimited — usage is tracked but never limited'
                                    : `Refreshed monthly${resets ? ` — next refresh ${resets}` : ''}`}
                            </CardDescription>
                        </div>
                        <span className="text-2xl font-semibold tabular-nums">
                            {credits.unlimited ? '∞' : credits.balance.toLocaleString()}
                        </span>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {!credits.unlimited && credits.allowance > 0 && (
                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-orange-400"
                                    style={{
                                        width: `${Math.max(Math.min((credits.balance / credits.allowance) * 100, 100), 1)}%`,
                                    }}
                                />
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {credits.unlimited
                                ? 'Admin organization'
                                : `${credits.balance.toLocaleString()} of ${credits.allowance.toLocaleString()} monthly credits${resets ? ` · resets ${resets}` : ''}`}
                        </p>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Credits used</CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {usedThisMonth.toLocaleString()}
                            </CardTitle>
                            <CardDescription>this month</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Estimated external cost</CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {money(estimatedCost)}
                            </CardTitle>
                            <CardDescription>this month</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>By action</CardDescription>
                            <CardContent className="flex flex-col gap-1.5 p-0 pt-2">
                                {byAction.length === 0 && (
                                    <span className="text-sm text-muted-foreground">
                                        No usage yet
                                    </span>
                                )}
                                {byAction.map((row) => {
                                    const Icon = ACTION_ICONS[row.action] ?? Zap;
                                    return (
                                        <span
                                            key={row.action}
                                            className="flex items-center justify-between text-sm"
                                        >
                                            <span className="flex items-center gap-2 text-muted-foreground">
                                                <Icon className="size-3.5" />
                                                {ACTION_LABELS[row.action] ?? row.action}
                                            </span>
                                            <span className="tabular-nums">
                                                {row.units} · {money(row.cost)}
                                            </span>
                                        </span>
                                    );
                                })}
                            </CardContent>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Activity</CardTitle>
                        <CardDescription>Most recent credit transactions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No activity yet — run a PageSpeed analysis to get started.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>When</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Units</TableHead>
                                        <TableHead>Credits</TableHead>
                                        <TableHead>Est. cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.map((transaction) => (
                                        <TableRow key={transaction.id}>
                                            <TableCell className="text-muted-foreground">
                                                {new Date(transaction.created_at).toLocaleString()}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        transaction.credits >= 0
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {ACTION_LABELS[transaction.action] ?? transaction.action}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {transaction.user?.email ?? '—'}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {transaction.quantity}
                                            </TableCell>
                                            <TableCell
                                                className={
                                                    transaction.credits >= 0
                                                        ? 'font-medium text-emerald-500'
                                                        : 'font-medium tabular-nums'
                                                }
                                            >
                                                {transaction.credits > 0 ? '+' : ''}
                                                {transaction.credits}
                                            </TableCell>
                                            <TableCell className="tabular-nums text-muted-foreground">
                                                {transaction.cost_estimate
                                                    ? money(Number(transaction.cost_estimate))
                                                    : '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Usage.layout = {
    breadcrumbs: [
        {
            title: 'Usage',
            href: '/usage',
        },
    ],
};
