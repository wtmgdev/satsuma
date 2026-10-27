import { Form, Head } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { index as clientsIndex } from '@/routes/clients';
import type { Client } from '@/types';

export default function ClientEdit({
    client,
}: {
    client: Pick<Client, 'id' | 'name' | 'email' | 'phone' | 'notes'>;
}) {
    return (
        <>
            <Head title={`Edit ${client.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>{client.name}</CardTitle>
                        <CardDescription>
                            Update client details
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ClientController.update.form(client)}
                            className="max-w-xl"
                        >
                            {({ processing, errors }) => (
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="name">Name</FieldLabel>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            defaultValue={client.name}
                                        />
                                        <InputError message={errors.name} />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="email">Email</FieldLabel>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={client.email ?? ''}
                                        />
                                        <FieldDescription>
                                            Used for reports and notifications.
                                        </FieldDescription>
                                        <InputError message={errors.email} />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="phone">Phone</FieldLabel>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            defaultValue={client.phone ?? ''}
                                        />
                                        <InputError message={errors.phone} />
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="notes">Notes</FieldLabel>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={4}
                                            defaultValue={client.notes ?? ''}
                                        />
                                        <InputError message={errors.notes} />
                                    </Field>

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={processing}>
                                            Save Changes
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <a href={clientsIndex().url}>Cancel</a>
                                        </Button>
                                    </div>
                                </FieldGroup>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ClientEdit.layout = {
    breadcrumbs: [
        {
            title: 'Clients',
            href: clientsIndex().url,
        },
        {
            title: 'Edit',
            href: clientsIndex().url,
        },
    ],
};
