import { Form, router, usePage } from '@inertiajs/react';
import {
    ChevronsUpDown,
    CircleFadingPlus,
    Settings,
    SquareCheck,
} from 'lucide-react';
import { useState } from 'react';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { settings as organizationSettings } from '@/routes/organizations';
import type { Organization } from '@/types';

export function OrgSwitcher() {
    const page = usePage();
    const organizations = page.props.organizations;
    const current = page.props.currentOrganization;
    const [createOpen, setCreateOpen] = useState(false);

    if (!current) {
        return (
            <>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            onClick={() => setCreateOpen(true)}
                            tooltip={{ children: 'Create organization' }}
                        >
                            <AppLogo />
                            <span className="text-sm font-medium">Create organization</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <CreateOrgDialog open={createOpen} onOpenChange={setCreateOpen} />
            </>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={{ children: current.name }}
                            data-test="org-switcher"
                        >
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground text-sm font-semibold">
                                {current.name.charAt(0).toUpperCase()}
                            </span>
                            <span className="flex min-w-0 flex-col">
                                <span className="truncate text-sm font-medium">
                                    {current.name}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    Organization
                                </span>
                            </span>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={page.props.sidebarOpen ? 'bottom' : 'right'}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Organizations
                        </DropdownMenuLabel>
                        <DropdownMenuGroup>
                            {organizations.map((organization: Organization) => (
                                <DropdownMenuItem
                                    key={organization.id}
                                    onClick={() =>
                                        router.post(OrganizationController.switch.url(), {
                                            organization_id: organization.id,
                                        })
                                    }
                                >
                                    <span className="flex size-5 items-center justify-center rounded bg-muted text-[10px] font-semibold">
                                        {organization.name.charAt(0).toUpperCase()}
                                    </span>
                                    <span className="truncate">{organization.name}</span>
                                    {organization.id === current.id && (
                                        <SquareCheck className="ml-auto size-4" />
                                    )}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem onSelect={() => setCreateOpen(true)}>
                                <CircleFadingPlus />
                                New organization
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <a href={organizationSettings().url}>
                                    <Settings />
                                    Organization settings
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

export function CreateOrgDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New organization</DialogTitle>
                    <DialogDescription>
                        Organizations group your clients and websites.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrganizationController.store.form()}
                    onSuccess={() => onOpenChange(false)}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="org-name">Organization name</FieldLabel>
                                    <Input
                                        id="org-name"
                                        name="name"
                                        required
                                        placeholder="Acme Agency"
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-destructive">{errors.name}</p>
                                    )}
                                </Field>
                            </FieldGroup>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create Organization
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
