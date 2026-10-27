import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Globe,
    LayoutGrid,
    Palette,
    ShieldCheck,
    UserRound,
    UserRoundCog,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
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

const settingsNavItems: NavItem[] = [
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
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
    },
];

export function AppSidebar() {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const inSettings = isCurrentOrParentUrl('/settings');

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {inSettings ? (
                            <SidebarMenuButton
                                size="lg"
                                asChild
                                tooltip={{ children: 'Back to app' }}
                            >
                                <Link href={dashboard()} prefetch>
                                    <ArrowLeft />
                                    <span className="text-sm font-medium">
                                        Settings
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        ) : (
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={dashboard()} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={inSettings ? settingsNavItems : mainNavItems}
                    label={inSettings ? 'Settings' : 'Platform'}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
