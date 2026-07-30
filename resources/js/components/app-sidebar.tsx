import { Link, usePage } from '@inertiajs/react';
import { Bot, LayoutGrid } from 'lucide-react';
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
import type { NavItem } from '@/types';

type AdminNavItem = NavItem & {
    permission?: string;
};

const mainNavItems: AdminNavItem[] = [
    {
        title: '控制台',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'admin.dashboard.view',
    },
    {
        title: 'AI 管理',
        href: '/admin/ai',
        icon: Bot,
        permission: 'admin.ai.manage',
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const visibleItems = mainNavItems.filter(
        (item) =>
            !item.permission ||
            auth.roles.includes('super-admin') ||
            auth.permissions.includes(item.permission),
    );

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
                <NavMain items={visibleItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
