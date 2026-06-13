import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Package,
    RefreshCw,
    ShoppingBag,
    UserRound,
    Users,
    Warehouse,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { index as adminCustomersIndex } from '@/routes/admin/customers';
import { index as adminInventoryIndex } from '@/routes/admin/inventory';
import { index as adminOrdersIndex } from '@/routes/admin/orders';
import { index as adminProductsIndex } from '@/routes/admin/products';
import { index as adminSubscriptionsIndex } from '@/routes/admin/subscriptions';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

function useMainNavItems(): NavItem[] {
    const { auth } = usePage<{ auth: Auth }>().props;

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (auth.user?.permissions?.includes('users.view')) {
        items.push({
            title: 'Users',
            href: adminUsersIndex(),
            icon: Users,
        });
    }

    if (auth.user?.permissions?.includes('customers.view')) {
        items.push({
            title: 'Customers',
            href: adminCustomersIndex(),
            icon: UserRound,
        });
    }

    if (auth.user?.permissions?.includes('products.view')) {
        items.push({
            title: 'Products',
            href: adminProductsIndex(),
            icon: Package,
        });
    }

    if (auth.user?.permissions?.includes('orders.view')) {
        items.push({
            title: 'Orders',
            href: adminOrdersIndex(),
            icon: ShoppingBag,
        });
    }

    if (auth.user?.permissions?.includes('subscriptions.view')) {
        items.push({
            title: 'Subscriptions',
            href: adminSubscriptionsIndex(),
            icon: RefreshCw,
        });
    }

    if (auth.user?.permissions?.includes('inventory.view')) {
        items.push({
            title: 'Inventory',
            href: adminInventoryIndex(),
            icon: Warehouse,
        });
    }

    return items;
}

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    const mainNavItems = useMainNavItems();

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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
