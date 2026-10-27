import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Check,
    Copy,
    Download,
    ExternalLink,
    Eye,
    EyeOff,
    Pencil,
    Plug,
    RefreshCw,
    Trash2,
} from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import { index as sitesIndex, scan as siteScan, edit as siteEdit } from '@/routes/sites';
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
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Empty,
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
import type { Site, SitePlugin } from '@/types';

export default function SiteShow({
    site,
    agentToken,
}: {
    site: Site;
    agentToken: string;
}) {
    const runScan = () => {
        router.post(siteScan(site).url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={site.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>{site.name}</CardTitle>
                            <CardDescription>
                                <a
                                    href={site.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 hover:underline"
                                >
                                    {site.url}
                                    <ExternalLink />
                                </a>
                            </CardDescription>
                        </div>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" onClick={runScan}>
                                <RefreshCw data-icon="inline-start" />
                                Run Scan
                            </Button>
                            <Button size="sm" variant="outline" asChild>
                                <a href={siteEdit(site).url}>
                                    <Pencil data-icon="inline-start" />
                                    Edit
                                </a>
                            </Button>
                            <DeleteSiteButton site={site} />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-4 text-sm md:grid-cols-5">
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Client</dt>
                                <dd className="font-medium">{site.client?.name ?? '—'}</dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Status</dt>
                                <dd>
                                    <Badge
                                        variant={
                                            site.connection_status === 'online'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                    >
                                        {site.connection_status}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">WordPress</dt>
                                <dd className="font-medium">{site.wp_version ?? '—'}</dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">PHP</dt>
                                <dd className="font-medium">{site.php_version ?? '—'}</dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Last Ping</dt>
                                <dd className="font-medium">
                                    {site.last_ping_at ? timeAgo(site.last_ping_at) : 'Never'}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <AgentSetupCard siteUrl={site.url} token={agentToken} />

                <Card>
                    <CardHeader>
                        <CardTitle>Plugins</CardTitle>
                        <CardDescription>
                            Installed plugins and their update status.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {site.plugins?.length ? (
                            <ComponentTable items={site.plugins} />
                        ) : (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <RefreshCw />
                                    </EmptyMedia>
                                    <EmptyTitle>Nothing scanned yet</EmptyTitle>
                                    <EmptyDescription>
                                        Run a scan to fetch the plugin inventory.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Themes</CardTitle>
                        <CardDescription>
                            Installed themes and their update status.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {site.themes?.length ? (
                            <ComponentTable items={site.themes} />
                        ) : (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <RefreshCw />
                                    </EmptyMedia>
                                    <EmptyTitle>Nothing scanned yet</EmptyTitle>
                                    <EmptyDescription>
                                        Run a scan to fetch the theme inventory.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ComponentTable({ items }: { items: SitePlugin[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Slug</TableHead>
                    <TableHead>Version</TableHead>
                    <TableHead>Active</TableHead>
                    <TableHead>Update</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {items.map((item) => (
                    <TableRow key={item.id}>
                        <TableCell className="font-medium">{item.name}</TableCell>
                        <TableCell className="text-muted-foreground">{item.slug}</TableCell>
                        <TableCell className="text-muted-foreground">
                            {item.version ?? '—'}
                        </TableCell>
                        <TableCell>
                            <Badge variant={item.is_active ? 'outline' : 'secondary'}>
                                {item.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            {item.update_available ? (
                                <Badge variant="secondary">
                                    {item.version} → {item.new_version}
                                </Badge>
                            ) : (
                                <span className="text-sm text-muted-foreground">Up to date</span>
                            )}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function AgentSetupCard({ siteUrl, token }: { siteUrl: string; token: string }) {
    const [revealed, setRevealed] = useState(false);
    const [copied, setCopied] = useState(false);

    const copyToken = async () => {
        await navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Agent Setup</CardTitle>
                <CardDescription>
                    Connect the Shoal agent plugin on {siteUrl} so scans can
                    reach this site.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <ol className="flex flex-col gap-2 text-sm text-muted-foreground">
                    <li className="flex gap-2">
                        <span className="font-medium text-foreground">1.</span>
                        <span>
                            Download the{' '}
                            <a
                                href="/agent-plugin/download"
                                className="inline-flex items-center gap-1 font-medium text-foreground underline underline-offset-4"
                            >
                                Shoal agent plugin (.zip)
                                <Download />
                            </a>{' '}
                            and install it on the WordPress site via
                            Plugins → Add New → Upload Plugin.
                        </span>
                    </li>
                    <li className="flex gap-2">
                        <span className="font-medium text-foreground">2.</span>
                        <span>
                            Activate it, then store this site's token via{' '}
                            <code>wp shoal token {'<token>'}</code> or the{' '}
                            <code>SHOAL_AGENT_TOKEN</code> constant.
                        </span>
                    </li>
                    <li className="flex gap-2">
                        <span className="font-medium text-foreground">3.</span>
                        <span>Run a scan — the site should turn online.</span>
                    </li>
                </ol>

                <div className="flex flex-col gap-2">
                    <span className="text-sm font-medium">Site token</span>
                    <div className="flex gap-2">
                        <Input
                            readOnly
                            value={token}
                            type={revealed ? 'text' : 'password'}
                            className="font-mono text-xs"
                        />
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            onClick={() => setRevealed(!revealed)}
                            title={revealed ? 'Hide token' : 'Reveal token'}
                        >
                            {revealed ? <EyeOff /> : <Eye />}
                        </Button>
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            onClick={copyToken}
                            title="Copy token"
                        >
                            {copied ? <Check /> : <Copy />}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function DeleteSiteButton({ site }: { site: Site }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="destructive">
                    <Trash2 data-icon="inline-start" />
                    Delete
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

SiteShow.layout = {
    breadcrumbs: [
        {
            title: 'Sites',
            href: sitesIndex().url,
        },
    ],
};
