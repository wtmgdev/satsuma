import { Head, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Globe, Plug, RefreshCw, WifiOff } from 'lucide-react';
import { dashboard } from '@/routes';
import { scan as siteScan, show as siteShow } from '@/routes/sites';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { DashboardStats, Site } from '@/types';

type Props = {
    stats: DashboardStats;
    sites: Site[];
};

export default function Dashboard({ stats, sites }: Props) {
    const runScan = (site: Site) => {
        router.post(siteScan(site).url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <StatCard
                        title="Total Sites"
                        description="Connected WordPress sites"
                        value={stats.totalSites}
                        icon={Globe}
                    />
                    <StatCard
                        title="Online"
                        description="Responding to scans"
                        value={stats.onlineSites}
                        icon={RefreshCw}
                    />
                    <StatCard
                        title="Offline"
                        description="Failed last scan"
                        value={stats.offlineSites}
                        icon={WifiOff}
                    />
                    <StatCard
                        title="Pending Updates"
                        description="Plugins and themes"
                        value={stats.pendingUpdates}
                        icon={Plug}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Sites</CardTitle>
                        <CardDescription>
                            All WordPress sites connected to this account.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {sites.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Globe />
                                    </EmptyMedia>
                                    <EmptyTitle>No sites yet</EmptyTitle>
                                    <EmptyDescription>
                                        Add your first WordPress site to start
                                        monitoring it.
                                    </EmptyDescription>
                                </EmptyHeader>
                                <EmptyContent>
                                    <Button asChild variant="outline">
                                        <a href="/admin/sites">Add a site in the admin panel</a>
                                    </Button>
                                </EmptyContent>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Site</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>WordPress</TableHead>
                                        <TableHead>PHP</TableHead>
                                        <TableHead>Last Ping</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sites.map((site) => (
                                        <TableRow key={site.id}>
                                            <TableCell>
                                                <a
                                                    href={siteShow(site).url}
                                                    className="font-medium hover:underline"
                                                >
                                                    {site.name}
                                                </a>
                                                <div className="text-xs text-muted-foreground">
                                                    {site.url}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {site.client?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        site.connection_status === 'online'
                                                            ? 'default'
                                                            : 'destructive'
                                                    }
                                                >
                                                    {site.connection_status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {site.wp_version ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {site.php_version ? (
                                                    <Badge
                                                        variant={
                                                            comparePhp(site.php_version) < 0
                                                                ? 'destructive'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {site.php_version}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {site.last_ping_at
                                                    ? timeAgo(site.last_ping_at)
                                                    : 'Never'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => runScan(site)}
                                                >
                                                    <RefreshCw data-icon="inline-start" />
                                                    Run Scan
                                                </Button>
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

function StatCard({
    title,
    description,
    value,
    icon: Icon,
}: {
    title: string;
    description: string;
    value: number;
    icon: LucideIcon;
}) {
    return (
        <Card className="gap-2">
            <CardHeader>
                <CardDescription className="flex items-center justify-between">
                    {title}
                    <Icon className="text-muted-foreground" />
                </CardDescription>
                <CardTitle className="text-2xl tabular-nums">{value}</CardTitle>
            </CardHeader>
            <CardContent className="text-xs text-muted-foreground">
                {description}
            </CardContent>
        </Card>
    );
}

function comparePhp(version: string): number {
    const [major, minor = 0] = version.split('.').map(Number);
    return major - 8 || minor - 1;
}

function timeAgo(date: string): string {
    return new Intl.RelativeTimeFormat('en', { numeric: 'auto' }).format(
        Math.round((new Date(date).getTime() - Date.now()) / 60000),
        'minute',
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
