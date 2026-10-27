import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Globe, Pencil, Plus, RefreshCw, Trash2 } from 'lucide-react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import InputError from '@/components/input-error';
import { dashboard } from '@/routes';
import {
    edit as siteEdit,
    index as sitesIndex,
    scan as siteScan,
    show as siteShow,
} from '@/routes/sites';
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
    DialogHeader,
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Client, Site } from '@/types';

type Props = {
    sites: Site[];
    clients: Client[];
};

export default function SitesIndex({ sites, clients }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

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
                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button size="sm">
                                    <Plus data-icon="inline-start" />
                                    New Site
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Add Site</DialogTitle>
                                    <DialogDescription>
                                        Connect a WordPress site with the Shoal
                                        agent plugin.
                                    </DialogDescription>
                                </DialogHeader>
                                <CreateSiteForm clients={clients} />
                            </DialogContent>
                        </Dialog>
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
                                                        <a href={siteEdit(site).url}>
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

function CreateSiteForm({ clients }: { clients: Client[] }) {
    return (
        <Form {...SiteController.store.form()} className="flex flex-col gap-6">
            {({ processing, errors }) => (
                <>
                    <FieldGroup>
                        <Field>
                            <FieldLabel htmlFor="create-site-name">Site name</FieldLabel>
                            <Input
                                id="create-site-name"
                                name="name"
                                required
                                placeholder="My WordPress Site"
                            />
                            <InputError message={errors.name} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-site-url">Site URL</FieldLabel>
                            <Input
                                id="create-site-url"
                                name="url"
                                type="url"
                                required
                                placeholder="https://example.com"
                            />
                            <FieldDescription>
                                The base URL of the WordPress installation.
                            </FieldDescription>
                            <InputError message={errors.url} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-site-client">Client</FieldLabel>
                            <Select name="client_id">
                                <SelectTrigger id="create-site-client" className="w-full">
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
                            <FieldDescription>
                                Optional. Assign this site to a client.
                            </FieldDescription>
                            <InputError message={errors.client_id} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-site-token">API token</FieldLabel>
                            <Input
                                id="create-site-token"
                                name="api_token"
                                placeholder="Leave blank to auto-generate"
                            />
                            <FieldDescription>
                                Set this token in the Shoal agent plugin on the
                                WordPress site.
                            </FieldDescription>
                            <InputError message={errors.api_token} />
                        </Field>
                    </FieldGroup>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            Create Site
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
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
