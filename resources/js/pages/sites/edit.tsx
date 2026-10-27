import { Form, Head } from '@inertiajs/react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as sitesIndex, show as siteShow } from '@/routes/sites';
import type { Client, Site } from '@/types';

type Props = {
    site: Pick<Site, 'id' | 'client_id' | 'name' | 'url'>;
    clients: Client[];
};

export default function SiteEdit({ site, clients }: Props) {
    return (
        <>
            <Head title={`Edit ${site.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>{site.name}</CardTitle>
                        <CardDescription>
                            Update site connection details
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...SiteController.update.form(site)}
                            className="max-w-xl"
                        >
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

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={processing}>
                                            Save Changes
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <a href={siteShow(site).url}>Cancel</a>
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

SiteEdit.layout = {
    breadcrumbs: [
        {
            title: 'Sites',
            href: sitesIndex().url,
        },
        {
            title: 'Edit',
            href: sitesIndex().url,
        },
    ],
};
