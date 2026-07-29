import { Link } from '@inertiajs/react';
import { LayoutGrid, Palette, ShieldCheck, UserRound } from 'lucide-react';
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
import * as appearance from '@/routes/appearance';
import * as profile from '@/routes/profile';
import * as security from '@/routes/security';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: '控制台',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: '个人资料',
        href: profile.edit(),
        icon: UserRound,
    },
    {
        title: '安全设置',
        href: security.edit(),
        icon: ShieldCheck,
    },
    {
        title: '外观设置',
        href: appearance.edit(),
        icon: Palette,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
