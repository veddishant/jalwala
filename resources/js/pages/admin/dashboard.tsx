import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    IndianRupee,
    Package,
    RefreshCw,
    ShoppingBag,
    Users,
} from 'lucide-react';
import { StatCard } from '@/components/dashboard/stat-card';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index as customersIndex, wallet as customerWallet } from '@/routes/admin/customers';
import { index as ordersIndex, show as orderShow } from '@/routes/admin/orders';
import { index as productsIndex } from '@/routes/admin/products';
import { index as subscriptionsIndex } from '@/routes/admin/subscriptions';

type Stats = {
    active_customers: number;
    pending_orders: number;
    today_deliveries: number;
    active_subscriptions: number;
    low_wallet_customers: number;
    active_products: number;
};

type RecentOrder = {
    uuid: string;
    customer_name: string | null;
    customer_code: string | null;
    status: string;
    status_label: string;
    scheduled_date: string;
    total: string;
};

type LowWalletCustomer = {
    customer_id: number;
    customer_name: string | null;
    customer_code: string | null;
    balance: string;
    low_balance_threshold: string;
};

const orderStatusVariant = (status: string) => {
    switch (status) {
        case 'pending':
            return 'secondary' as const;
        case 'out_for_delivery':
            return 'default' as const;
        default:
            return 'outline' as const;
    }
};

export default function AdminDashboard({
    tenant,
    stats,
    recentOrders,
    lowWalletCustomers,
}: {
    tenant: { name: string; currency: string };
    stats: Stats;
    recentOrders: RecentOrder[];
    lowWalletCustomers: LowWalletCustomer[];
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title={tenant.name}
                    description="Overview of customers, orders, subscriptions, and wallet alerts."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard
                        label="Active customers"
                        value={stats.active_customers}
                        icon={Users}
                        href={customersIndex()}
                    />
                    <StatCard
                        label="Pending orders"
                        value={stats.pending_orders}
                        description="Awaiting delivery"
                        icon={ShoppingBag}
                        href={ordersIndex()}
                    />
                    <StatCard
                        label="Today's deliveries"
                        value={stats.today_deliveries}
                        icon={Package}
                        href={ordersIndex()}
                    />
                    <StatCard
                        label="Active subscriptions"
                        value={stats.active_subscriptions}
                        icon={RefreshCw}
                        href={subscriptionsIndex()}
                    />
                    <StatCard
                        label="Low wallet balance"
                        value={stats.low_wallet_customers}
                        description="Below threshold"
                        icon={AlertTriangle}
                        valueClassName={
                            stats.low_wallet_customers > 0
                                ? 'text-amber-600'
                                : undefined
                        }
                        href={customersIndex()}
                    />
                    <StatCard
                        label="Active products"
                        value={stats.active_products}
                        icon={Package}
                        href={productsIndex()}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                            <div>
                                <CardTitle>Upcoming orders</CardTitle>
                                <CardDescription>
                                    Open orders by scheduled date.
                                </CardDescription>
                            </div>
                            <Link
                                href={ordersIndex()}
                                className="text-sm font-medium text-primary"
                            >
                                View all →
                            </Link>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            {recentOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No open orders right now.
                                </p>
                            ) : (
                                recentOrders.map((order) => (
                                    <Link
                                        key={order.uuid}
                                        href={orderShow(order.uuid)}
                                        className="flex flex-col gap-2 rounded-lg border p-3 transition-colors hover:border-primary/40 hover:bg-primary/5 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {order.customer_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {order.customer_code} ·{' '}
                                                {order.scheduled_date}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={orderStatusVariant(
                                                    order.status,
                                                )}
                                            >
                                                {order.status_label}
                                            </Badge>
                                            <span className="text-sm font-medium">
                                                {tenant.currency}{' '}
                                                {order.total}
                                            </span>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <IndianRupee className="size-5 text-amber-600" />
                                Low wallet balances
                            </CardTitle>
                            <CardDescription>
                                Customers who may need a top-up soon.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            {lowWalletCustomers.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    All customer wallets are above their
                                    thresholds.
                                </p>
                            ) : (
                                lowWalletCustomers.map((customer) => (
                                    <Link
                                        key={customer.customer_id}
                                        href={customerWallet(
                                            customer.customer_id,
                                        )}
                                        className="flex flex-col gap-1 rounded-lg border p-3 transition-colors hover:border-primary/40 hover:bg-primary/5 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {customer.customer_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {customer.customer_code}
                                            </p>
                                        </div>
                                        <div className="text-sm">
                                            <span className="font-medium text-amber-600">
                                                {tenant.currency}{' '}
                                                {customer.balance}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {' '}
                                                / {customer.low_balance_threshold}{' '}
                                                min
                                            </span>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
