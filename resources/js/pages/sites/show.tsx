import { Form, Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    Check,
    Copy,
    Download,
    ExternalLink,
    Eye,
    EyeOff,
    Globe,
    Pencil,
    PlugZap,
    Puzzle,
    RefreshCw,
    Trash2,
} from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import InputError from '@/components/input-error';
import { PhpLogo, WpLogo } from '@/components/brand-icons';
import { usePollWhile } from '@/hooks/use-poll-while';
import {
    index as sitesIndex,
    ping as sitePing,
    scan as siteScan,
} from '@/routes/sites';
import { Badge } from '@/components/ui/badge';
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
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import type { Client, Site, SitePlugin } from '@/types';
import type { PhpInfo, WpInfo } from '@/components/version-badges';
import { versionCompare } from '@/components/version-badges';

type VulnerabilitySummary = Record<string, { severity: string; count: number }>;

function WpVersionText({ wp }: { wp: WpInfo }) {
    if (!wp.installed) {
        return <span className="text-muted-foreground">—</span>;
    }
    return (
        <span className="flex items-baseline gap-1.5">
            {wp.installed}
            {wp.update_available && wp.latest && (
                <span className="text-sm font-normal text-warning">
                    → {wp.latest}
                </span>
            )}
        </span>
    );
}

function PhpVersionText({ php }: { php: PhpInfo }) {
    if (!php.installed) {
        return <span className="text-muted-foreground">—</span>;
    }
    return (
        <span className="flex items-baseline gap-1.5">
            {php.installed}
            {php.eol ? (
                <span className="text-sm font-normal text-destructive">EOL</span>
            ) : (
                php.latest_patch
                && versionCompare(php.installed, php.latest_patch) < 0 && (
                    <span className="text-sm font-normal text-warning">
                        → {php.latest_patch}
                    </span>
                )
            )}
        </span>
    );
}

export default function SiteShow({
    site,
    clients,
    agentToken,
    versionInfo,
    agentPluginVersion,
    vulnerabilities,
}: {
    site: Site;
    clients: Client[];
    agentToken: string;
    versionInfo: {
        php: PhpInfo;
        wp: WpInfo;
    };
    agentPluginVersion: string | null;
    vulnerabilities: VulnerabilitySummary;
}) {
    const runScan = () => {
        router.post(siteScan(site).url, {}, { preserveScroll: true });
    };

    const testConnection = () => {
        router.post(sitePing(site).url, {}, { preserveScroll: true });
    };

    const working = site.scan_status !== 'idle';

    usePollWhile(working || site.connection_status === 'offline', ['site']);

    const previousScanStatus = useRef(site.scan_status);
    useEffect(() => {
        if (
            previousScanStatus.current !== 'idle'
            && site.scan_status === 'idle'
        ) {
            if (site.connection_status === 'online') {
                toast.success('Scan complete — inventory updated.');
            } else {
                toast.warning('Scan finished, but the site could not be reached.');
            }
        }

        previousScanStatus.current = site.scan_status;
    }, [site.scan_status, site.connection_status]);

    return (
        <>
            <Head title={site.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>{site.name}</CardTitle>
                            <CardDescription>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="h-7 px-2.5 text-xs font-normal"
                                    asChild
                                >
                                    <a
                                        href={site.url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        {site.url.replace(/^https?:\/\//, '')}
                                        <ExternalLink data-icon="inline-end" />
                                    </a>
                                </Button>
                            </CardDescription>
                        </div>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" onClick={testConnection}>
                                <PlugZap data-icon="inline-start" />
                                Test Connection
                            </Button>
                            <Button size="sm" variant="outline" onClick={runScan} disabled={working}>
                                {working ? (
                                    <Spinner data-icon="inline-start" />
                                ) : (
                                    <RefreshCw data-icon="inline-start" />
                                )}
                                {working ? 'Scanning…' : 'Run Scan'}
                            </Button>
                            <AgentSetupDialog
                                siteUrl={site.url}
                                token={agentToken}
                                pluginVersion={agentPluginVersion}
                            />
                            <EditSiteDialog site={site} clients={clients} />
                            <DeleteSiteButton site={site} />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-4 text-sm md:grid-cols-6">
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Client</dt>
                                <dd className="font-medium">{site.client?.name ?? '—'}</dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Status</dt>
                                <dd className="flex items-center gap-2">
                                    <Badge
                                        variant={
                                            site.connection_status === 'online'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                    >
                                        {site.connection_status}
                                    </Badge>
                                    {working && (
                                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Spinner className="size-3" />
                                            {site.scan_status === 'queued'
                                                ? 'Scan queued…'
                                                : 'Scanning…'}
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">WordPress</dt>
                                <dd className="flex items-center gap-1.5 text-base font-medium">
                                    <WpLogo className="size-4 shrink-0 text-muted-foreground" />
                                    <WpVersionText wp={versionInfo.wp} />
                                </dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">PHP</dt>
                                <dd className="flex items-center gap-1.5 text-base font-medium">
                                    <PhpLogo className="size-4 shrink-0 text-muted-foreground" />
                                    <PhpVersionText php={versionInfo.php} />
                                </dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Last Ping</dt>
                                <dd className="font-medium">
                                    {site.last_ping_at ? timeAgo(site.last_ping_at) : 'Never'}
                                </dd>
                            </div>
                            <div className="flex flex-col gap-1">
                                <dt className="text-xs text-muted-foreground">Last Scan</dt>
                                <dd className="font-medium">
                                    {site.last_scan_at ? timeAgo(site.last_scan_at) : 'Never'}
                                </dd>
                            </div>
                            {site.whois?.expires_at && (
                                <div className="flex flex-col gap-1">
                                    <dt className="text-xs text-muted-foreground">Domain expires</dt>
                                    <dd className="font-medium" title={site.whois.registrar ?? undefined}>
                                        {new Date(site.whois.expires_at).toLocaleDateString()}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                <Tabs defaultValue="plugins">
                    <TabsList>
                        <TabsTrigger value="plugins">
                            Plugins{site.plugins?.length ? ` · ${site.plugins.length}` : ''}
                        </TabsTrigger>
                        <TabsTrigger value="themes">
                            Themes{site.themes?.length ? ` · ${site.themes.length}` : ''}
                        </TabsTrigger>
                        <TabsTrigger value="server">Server info</TabsTrigger>
                    </TabsList>

                    <TabsContent value="plugins">
                        <Card>
                            <CardHeader>
                                <CardTitle>Plugins</CardTitle>
                                <CardDescription>
                                    Installed plugins and their update status.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {site.plugins?.length ? (
                                    <ComponentTable items={site.plugins} type="plugin" vulnerabilities={vulnerabilities} />
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
                    </TabsContent>

                    <TabsContent value="themes">
                        <Card>
                            <CardHeader>
                                <CardTitle>Themes</CardTitle>
                                <CardDescription>
                                    Installed themes and their update status.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {site.themes?.length ? (
                                    <ComponentTable items={site.themes} type="theme" vulnerabilities={vulnerabilities} />
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
                    </TabsContent>

                    <TabsContent value="server">
                        <Card>
                            <CardHeader>
                                <CardTitle>Domain</CardTitle>
                                <CardDescription>
                                    {site.whois
                                        ? `${site.whois.domain ?? site.url} — from RDAP${site.whois_checked_at ? ` · checked ${timeAgo(site.whois_checked_at)}` : ''}`
                                        : 'Domain registration details, refreshed daily via RDAP.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {site.whois ? (
                                    <dl className="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                        <div className="flex flex-col gap-1">
                                            <dt className="text-xs text-muted-foreground">Registrar</dt>
                                            <dd className="font-medium">
                                                {site.whois.registrar ?? '—'}
                                            </dd>
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <dt className="text-xs text-muted-foreground">Registered</dt>
                                            <dd className="font-medium">
                                                {site.whois.registered_at
                                                    ? new Date(site.whois.registered_at).toLocaleDateString()
                                                    : '—'}
                                            </dd>
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <dt className="text-xs text-muted-foreground">Expires</dt>
                                            <dd className="font-medium">
                                                {site.whois.expires_at
                                                    ? new Date(site.whois.expires_at).toLocaleDateString()
                                                    : '—'}
                                            </dd>
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <dt className="text-xs text-muted-foreground">Nameservers</dt>
                                            <dd className="font-medium">
                                                {site.whois.nameservers?.length
                                                    ? site.whois.nameservers.join(', ')
                                                    : '—'}
                                            </dd>
                                        </div>
                                    </dl>
                                ) : (
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <Globe />
                                            </EmptyMedia>
                                            <EmptyTitle>No domain info yet</EmptyTitle>
                                            <EmptyDescription>
                                                It will appear after the next scheduled whois check.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

function ComponentTable({
    items,
    type,
    vulnerabilities,
}: {
    items: SitePlugin[];
    type: 'plugin' | 'theme';
    vulnerabilities: VulnerabilitySummary;
}) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Slug</TableHead>
                    <TableHead>Version</TableHead>
                    <TableHead>Active</TableHead>
                    <TableHead>Update</TableHead>
                    <TableHead>Security</TableHead>
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
                        <TableCell>
                            <SeverityBadge summary={vulnerabilities[`${type}:${item.slug}`]} />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function AgentSetupDialog({
    siteUrl,
    token,
    pluginVersion,
}: {
    siteUrl: string;
    token: string;
    pluginVersion: string | null;
}) {
    const [revealed, setRevealed] = useState(false);
    const [copied, setCopied] = useState(false);

    const copyToken = async () => {
        await navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Puzzle data-icon="inline-start" />
                    Agent setup
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>Agent Setup</DialogTitle>
                <DialogDescription>
                    Connect the Satsuma agent plugin on {siteUrl} so scans can
                    reach this site.
                </DialogDescription>
                <ol className="flex flex-col gap-2 text-sm text-muted-foreground">
                    <li className="flex gap-2">
                        <span className="font-medium text-foreground">1.</span>
                        <span>
                            Download the{' '}
                            <a
                                href="/agent-plugin/download"
                                className="inline-flex items-center gap-1 font-medium text-foreground underline underline-offset-4"
                            >
                                Satsuma agent plugin (.zip){pluginVersion ? ` v${pluginVersion}` : ''}
                                <Download />
                            </a>{' '}
                            and install it on the WordPress site via
                            Plugins → Add New → Upload Plugin.
                        </span>
                    </li>
                    <li className="flex gap-2">
                        <span className="font-medium text-foreground">2.</span>
                        <span>
                            Activate it, then paste the token below into{' '}
                            <code>Settings → Satsuma Agent</code> in the site's
                            wp-admin.
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
            </DialogContent>
        </Dialog>
    );
}

function EditSiteDialog({ site, clients }: { site: Site; clients: Client[] }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Pencil data-icon="inline-start" />
                    Edit
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>Edit {site.name}</DialogTitle>
                <DialogDescription>
                    Update site connection details.
                </DialogDescription>
                <Form {...SiteController.update.form(site)}>
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field>
                                <FieldLabel htmlFor="name">Site name</FieldLabel>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={site.name}
                                />
                                <InputError message={errors.name} />
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="url">Site URL</FieldLabel>
                                <Input
                                    id="url"
                                    name="url"
                                    type="url"
                                    required
                                    defaultValue={site.url}
                                />
                                <InputError message={errors.url} />
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="client_id">Client</FieldLabel>
                                <Select
                                    name="client_id"
                                    defaultValue={site.client_id ? String(site.client_id) : undefined}
                                >
                                    <SelectTrigger id="client_id" className="w-full">
                                        <SelectValue placeholder="No client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {clients.map((client) => (
                                                <SelectItem key={client.id} value={String(client.id)}>
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.client_id} />
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="api_token">API token</FieldLabel>
                                <Input
                                    id="api_token"
                                    name="api_token"
                                    placeholder="Unchanged"
                                />
                                <FieldDescription>
                                    Leave blank to keep the current token.
                                </FieldDescription>
                                <InputError message={errors.api_token} />
                            </Field>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                            </DialogFooter>
                        </FieldGroup>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
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

const severityVariant: Record<string, 'destructive' | 'warning' | 'secondary' | 'outline'> = {
    critical: 'destructive',
    high: 'warning',
    medium: 'secondary',
    low: 'outline',
};

function SeverityBadge({ summary }: { summary?: { severity: string; count: number } }) {
    if (!summary) {
        return <span className="text-sm text-muted-foreground">No issues</span>;
    }

    return (
        <Badge variant={severityVariant[summary.severity] ?? 'outline'}>
            {summary.count} {summary.severity}
        </Badge>
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
