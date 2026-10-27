import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Globe, Pencil, Plus, RefreshCw, Trash2 } from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import { CreateSiteDialog } from '@/components/create-site-dialog';
import { PhpVersionBadge } from '@/components/version-badges';
import { Download } from 'lucide-react';
import { dashboard } from '@/routes';
import {
    index as sitesIndex,
    ping as sitePing,
    scan as siteScan,
    show as siteShow,
} from '@/routes/sites';
import { usePollWhile } from '@/hooks/use-poll-while';
import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Client, Site } from '@/types';

type PhpInfo = {
    installed: string | null;
    latest_patch: string | null;
    eol: boolean;
    eol_date: string | null;
};

type WpInfo = {
    installed: string | null;
    latest: string | null;
    update_available: boolean;
};

type Props = {
    sites: Site[];
    clients: Client[];
    versionInfo: Record<number, { php: PhpInfo; wp: WpInfo }>;
    agentPluginVersion: string | null;
};

export default function SitesIndex({ sites, clients, versionInfo, agentPluginVersion }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const working = sites.some(
        (site) => site.scan_status !== 'idle',
    );

    usePollWhile(working, ['sites']);

    const runScan = (site: Site) => {
        router.post(siteScan(site).url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Sites" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Sites</CardTitle>
                            <CardDescription>
                                All WordPress sites connected to this account.
                            </CardDescription>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button size="sm" variant="outline" asChild title="Always the latest version">
                                <a href="/agent-plugin/download">
                                    <Download data-icon="inline-start" />
                                    Agent plugin{agentPluginVersion ? ` v${agentPluginVersion}` : ''}
                                </a>
                            </Button>
                            <CreateSiteDialog
                                clients={clients}
                                open={createOpen}
                                onOpenChange={setCreateOpen}
                                trigger={
                                    <Button size="sm">
                                        <Plus data-icon="inline-start" />
                                        New Site
                                    </Button>
                                }
                            />
                        </div>
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
                                    <Button onClick={() => setCreateOpen(true)}>
                                        <Plus data-icon="inline-start" />
                                        Add your first site
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
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            site.connection_status === 'online'
                                                                ? 'default'
                                                                : 'destructive'
                                                        }
                                                    >
                                                        {site.connection_status}
                                                    </Badge>
                                                    {site.scan_status !== 'idle' && (
                                                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                            <Spinner className="size-3" />
                                                            {site.scan_status === 'queued'
                                                                ? 'Scan queued…'
                                                                : 'Scanning…'}
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {site.wp_version ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <PhpVersionBadge php={versionInfo[site.id]?.php ?? { installed: site.php_version, latest_patch: null, eol: false, eol_date: null }} />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {site.last_ping_at
                                                    ? timeAgo(site.last_ping_at)
                                                    : 'Never'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => runScan(site)}
                                                        title="Run Scan"
                                                    >
                                                        <RefreshCw />
                                                    </Button>
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        asChild
                                                        title="Edit"
                                                    >
                                                        <a href={siteShow(site).url}>
                                                            <Pencil />
                                                        </a>
                                                    </Button>
                                                    <DeleteSiteButton site={site} />
                                                </div>
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

function DeleteSiteButton({ site }: { site: Site }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="icon" variant="ghost" title="Delete">
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Delete {site.name}?</DialogTitle>
                <DialogDescription>
                    This removes the site and its scanned plugin/theme
                    inventory. This cannot be undone.
                </DialogDescription>
                <Form {...SiteController.destroy.form(site)} options={{ preserveScroll: true }}>
                    {({ processing }) => (
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" variant="destructive" disabled={processing}>
                                Delete Site
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function timeAgo(date: string): string {
    return new Intl.RelativeTimeFormat('en', { numeric: 'auto' }).format(
        Math.round((new Date(date).getTime() - Date.now()) / 60000),
        'minute',
    );
}

SitesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Sites',
            href: sitesIndex().url,
        },
    ],
};
