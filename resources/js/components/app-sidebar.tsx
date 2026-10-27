import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Globe,
    LayoutGrid,
    Palette,
    ShieldCheck,
    UserRound,
    UserRoundCog,
} from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NotificationBell } from '@/components/notification-bell';
import { NavUser } from '@/components/nav-user';
import { OrgSwitcher } from '@/components/org-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { index as clientsIndex } from '@/routes/clients';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as sitesIndex } from '@/routes/sites';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Sites',
        href: sitesIndex().url,
        icon: Globe,
    },
    {
        title: 'Clients',
        href: clientsIndex().url,
        icon: UserRound,
    },
];

export function AppSidebar() {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const inSettings = isCurrentOrParentUrl('/settings');
    const hasOrganization = usePage().props.currentOrganization !== null;

    const settingsNavItems = [
        {
            title: 'Profile',
            href: editProfile(),
            icon: UserRoundCog,
        },
        {
            title: 'Security',
            href: editSecurity(),
            icon: ShieldCheck,
        },
        ...(hasOrganization
            ? [
                {
                    title: 'Organization',
                    href: '/settings/organization',
                    icon: Building2,
                },
            ]
            : []),
        {
            title: 'Appearance',
            href: editAppearance(),
            icon: Palette,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                {inSettings ? (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                size="lg"
                                asChild
                                tooltip={{ children: 'Back to app' }}
                            >
                                <Link href={dashboard()} prefetch>
                                    <ArrowLeft />
                                    <span className="text-sm font-medium">Settings</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                ) : (
                    <OrgSwitcher />
                )}
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={inSettings ? settingsNavItems : mainNavItems}
                    label={inSettings ? 'Settings' : 'Platform'}
                />
                {!inSettings && (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <NotificationBell />
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
