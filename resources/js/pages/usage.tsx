import { Head } from '@inertiajs/react';
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
    purchased: number;
    allowance: number;
    unlimited: boolean;
    resetsAt: string | null;
};

type ActionUsage = {
    action: string;
    units: number;
    credits: number;
};

type Transaction = {
    id: number;
    action: string;
    credits: number;
    quantity: number;
    description: string | null;
    created_at: string;
    user?: { email: string } | null;
    site?: { name: string } | null;
};

const ACTION_LABELS: Record<string, string> = {
    pagespeed_analysis: 'PageSpeed analysis',
    ai_summary: 'AI summary',
    crawl_page: 'Crawl page',
    topup: 'Top-up',
    grant: 'Bonus grant',
    monthly_reset: 'Monthly reset',
    monthly_expiry: 'Expired monthly credits',
};

const ACTION_ICONS: Record<string, typeof Gauge> = {
    pagespeed_analysis: Gauge,
    ai_summary: Sparkles,
    crawl_page: BarChart3,
};

export default function Usage({
    credits,
    usedThisMonth,
    byAction,
    transactions,
}: {
    credits: Credits;
    usedThisMonth: number;
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
                            <CardDescription>Monthly balance</CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {credits.unlimited
                                    ? 'Unlimited'
                                    : `${credits.balance.toLocaleString()} / ${credits.allowance.toLocaleString()}`}
                            </CardTitle>
                            <CardDescription>
                                {credits.unlimited
                                    ? 'usage is tracked but never limited'
                                    : resets
                                      ? `resets ${resets}`
                                      : 'refreshed monthly'}
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Purchased credits</CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {credits.purchased.toLocaleString()}
                            </CardTitle>
                            <CardDescription>never expire</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>By action</CardTitle>
                        <CardDescription>What consumed credits this month</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-1.5">
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
                                        {row.units} units · {row.credits} credits
                                    </span>
                                </span>
                            );
                        })}
                    </CardContent>
                </Card>

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
                                        <TableHead>Site</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Credits</TableHead>
                                        <TableHead>Description</TableHead>
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
                                                {transaction.site?.name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {transaction.user?.email ?? '—'}
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
                                            <TableCell className="text-muted-foreground">
                                                {transaction.description ?? '—'}
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
