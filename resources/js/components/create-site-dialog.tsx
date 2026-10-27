import { Form } from '@inertiajs/react';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import type { Client } from '@/types';

type Props = {
    clients: Client[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger?: React.ReactNode;
};

export function CreateSiteDialog({ clients, open, onOpenChange, trigger }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Site</DialogTitle>
                    <DialogDescription>
                        Connect a WordPress site with the Satsuma agent plugin.
                    </DialogDescription>
                </DialogHeader>
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
                                        Set this token in the Satsuma agent plugin on the
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
            </DialogContent>
        </Dialog>
    );
}
