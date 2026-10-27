import { Form, Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { update as organizationUpdate } from '@/routes/organizations';
import type { OrgMember } from '@/types';

type Props = {
    organization: { id: number; name: string; slug: string };
    members: OrgMember[];
    canManageMembers: boolean;
    canUpdate: boolean;
};

const roleBadgeVariant = {
    owner: 'warning',
    admin: 'secondary',
    member: 'outline',
} as const;

export default function OrganizationSettings({
    organization,
    members,
    canManageMembers,
    canUpdate,
}: Props) {
    return (
        <>
            <Head title="Organization" />

            <Heading
                title="Organization"
                description="Manage your organization and its members"
            />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>
                            The organization name groups your clients and websites.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...organizationUpdate.form()}
                            options={{ preserveScroll: true }}
                            className="max-w-md"
                        >
                            {({ processing, errors }) => (
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="org-name">Name</FieldLabel>
                                        <Input
                                            id="org-name"
                                            name="name"
                                            required
                                            defaultValue={organization.name}
                                            disabled={!canUpdate}
                                        />
                                        <InputError message={errors.name} />
                                    </Field>
                                    {canUpdate && (
                                        <Field>
                                            <Button type="submit" disabled={processing}>
                                                Save
                                            </Button>
                                        </Field>
                                    )}
                                </FieldGroup>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Members</CardTitle>
                        <CardDescription>
                            People with access to this organization's sites and clients.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-6">
                        {canManageMembers && <AddMemberForm />}

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    {canManageMembers && (
                                        <TableHead className="text-right">Actions</TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {members.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell className="font-medium">
                                            {member.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {member.email}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={roleBadgeVariant[member.role]}>
                                                {member.role}
                                            </Badge>
                                        </TableCell>
                                        {canManageMembers && (
                                            <TableCell className="text-right">
                                                {member.role !== 'owner' && (
                                                    <MemberActions member={member} />
                                                )}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function AddMemberForm() {
    return (
        <Form
            {...OrganizationController.addMember.form()}
            options={{ preserveScroll: true }}
            className="max-w-md"
        >
            {({ processing, errors }) => (
                <FieldGroup>
                    <Field>
                        <FieldLabel htmlFor="member-email">Add member</FieldLabel>
                        <div className="flex gap-2">
                            <Input
                                id="member-email"
                                name="email"
                                type="email"
                                required
                                placeholder="teammate@example.com"
                            />
                            <Select name="role" defaultValue="member">
                                <SelectTrigger className="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="member">Member</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <Button type="submit" disabled={processing}>
                                Add
                            </Button>
                        </div>
                        <FieldDescription>
                            The user must already have a Shoal account.
                        </FieldDescription>
                        <InputError message={errors.email} />
                    </Field>
                </FieldGroup>
            )}
        </Form>
    );
}

function MemberActions({ member }: { member: OrgMember }) {
    return (
        <div className="flex justify-end gap-1">
            <Select
                name="role"
                defaultValue={member.role === 'owner' ? 'member' : member.role}
                onValueChange={(role) =>
                    router.patch(
                        OrganizationController.updateMember.url({ member: member.id }),
                        { role },
                        { preserveScroll: true },
                    )
                }
            >
                <SelectTrigger size="sm" className="w-28">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem value="member">Member</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <Dialog>
                <DialogTrigger asChild>
                    <Button size="icon" variant="ghost" title="Remove member">
                        <Trash2 />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Remove {member.name}?</DialogTitle>
                    <DialogDescription>
                        They will lose access to this organization's sites and
                        clients.
                    </DialogDescription>
                    <Form
                        {...OrganizationController.removeMember.form({ member: member.id })}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" variant="destructive" disabled={processing}>
                                    Remove Member
                                </Button>
                            </DialogFooter>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
