import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, UserRound } from 'lucide-react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import InputError from '@/components/input-error';
import { edit as clientEdit, index as clientsIndex } from '@/routes/clients';
import { dashboard } from '@/routes';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Client } from '@/types';

type Props = {
    clients: (Client & { sites_count: number })[];
};

export default function ClientsIndex({ clients }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <>
            <Head title="Clients" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Clients</CardTitle>
                            <CardDescription>
                                Group sites under clients for reporting and access.
                            </CardDescription>
                        </div>
                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button size="sm">
                                    <Plus data-icon="inline-start" />
                                    New Client
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>New Client</DialogTitle>
                                    <DialogDescription>
                                        Create a client to group their WordPress
                                        sites.
                                    </DialogDescription>
                                </DialogHeader>
                                <CreateClientForm onSuccess={() => setCreateOpen(false)} />
                            </DialogContent>
                        </Dialog>
                    </CardHeader>
                    <CardContent>
                        {clients.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <UserRound />
                                    </EmptyMedia>
                                    <EmptyTitle>No clients yet</EmptyTitle>
                                    <EmptyDescription>
                                        Create a client to group their WordPress
                                        sites.
                                    </EmptyDescription>
                                </EmptyHeader>
                                <EmptyContent>
                                    <Button onClick={() => setCreateOpen(true)}>
                                        <Plus data-icon="inline-start" />
                                        Create your first client
                                    </Button>
                                </EmptyContent>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Phone</TableHead>
                                        <TableHead className="text-right">Sites</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {clients.map((client) => (
                                        <TableRow key={client.id}>
                                            <TableCell>
                                                <a
                                                    href={clientEdit(client).url}
                                                    className="font-medium hover:underline"
                                                >
                                                    {client.name}
                                                </a>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {client.email ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {client.phone ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Badge variant="secondary">
                                                    {client.sites_count}
                                                </Badge>
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

function CreateClientForm({ onSuccess }: { onSuccess: () => void }) {
    return (
        <Form
            {...ClientController.store.form()}
            onSuccess={onSuccess}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <FieldGroup>
                        <Field>
                            <FieldLabel htmlFor="create-client-name">Name</FieldLabel>
                            <Input
                                id="create-client-name"
                                name="name"
                                required
                                placeholder="Acme Inc"
                            />
                            <InputError message={errors.name} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-client-email">Email</FieldLabel>
                            <Input
                                id="create-client-email"
                                name="email"
                                type="email"
                                placeholder="billing@acme.com"
                            />
                            <FieldDescription>
                                Optional. Used for reports and notifications.
                            </FieldDescription>
                            <InputError message={errors.email} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-client-phone">Phone</FieldLabel>
                            <Input
                                id="create-client-phone"
                                name="phone"
                                placeholder="+1 555 000 0000"
                            />
                            <InputError message={errors.phone} />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="create-client-notes">Notes</FieldLabel>
                            <Textarea id="create-client-notes" name="notes" rows={3} />
                            <InputError message={errors.notes} />
                        </Field>
                    </FieldGroup>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            Create Client
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

ClientsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Clients',
            href: clientsIndex().url,
        },
    ],
};
