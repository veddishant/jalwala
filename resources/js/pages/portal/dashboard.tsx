import { Head, Link, usePage } from '@inertiajs/react';
import {
    Calendar,
    IndianRupee,
    Package,
    RefreshCw,
    ShoppingBag,
    Wallet,
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
import { dashboard } from '@/routes/portal';
import { index as depositsIndex } from '@/routes/portal/deposits';
import { index as ordersIndex, show as orderShow } from '@/routes/portal/orders';
import { show as subscriptionShow } from '@/routes/portal/subscription';
import { index as walletIndex } from '@/routes/portal/wallet';
import type { Auth } from '@/types';

type Summary = {
    customer: {
        name: string;
        code: string;
        status: string;
    };
    wallet: {
        balance: string;
        low_balance_threshold: string | null;
        is_low: boolean;
    } | null;
    deposit: {
        balance: string;
        held_jar_count: number;
    } | null;
    subscription: {
        status: string;
        status_label: string;
        paused_until: string | null;
    } | null;
    pending_orders: number;
    next_delivery_date: string | null;
};

type RecentOrder = {
    uuid: string;
    status: string;
    status_label: string;
    scheduled_date: string;
    total: string;
};

const orderStatusVariant = (status: string) => {
    switch (status) {
        case 'delivered':
        case 'completed':
            return 'default' as const;
        case 'cancelled':
        case 'failed':
            return 'destructive' as const;
        default:
            return 'secondary' as const;
    }
};

export default function PortalDashboard({
    summary,
    recentOrders,
}: {
    summary: Summary;
    recentOrders: RecentOrder[];
}) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="My account" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title={`Hello, ${summary.customer.name}`}
                    description={`Account ${summary.customer.code} · Welcome back${auth.user?.name !== summary.customer.name ? `, ${auth.user?.name}` : ''}.`}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Wallet balance"
                        value={
                            summary.wallet
                                ? `₹${summary.wallet.balance}`
                                : '—'
                        }
                        description={
                            summary.wallet?.is_low
                                ? 'Below recommended balance'
                                : undefined
                        }
                        icon={Wallet}
                        href={walletIndex()}
                        valueClassName={
                            summary.wallet?.is_low
                                ? 'text-amber-600'
                                : 'text-green-600'
                        }
                    />
                    <StatCard
                        label="Jar deposit held"
                        value={
                            summary.deposit
                                ? `₹${summary.deposit.balance}`
                                : '—'
                        }
                        description={
                            summary.deposit
                                ? `${summary.deposit.held_jar_count} jars on deposit`
                                : undefined
                        }
                        icon={IndianRupee}
                        href={depositsIndex()}
                    />
                    <StatCard
                        label="Pending orders"
                        value={summary.pending_orders}
                        icon={ShoppingBag}
                        href={ordersIndex()}
                    />
                    <StatCard
                        label="Subscription"
                        value={
                            summary.subscription?.status_label ?? 'None'
                        }
                        description={
                            summary.subscription?.paused_until
                                ? `Paused until ${summary.subscription.paused_until}`
                                : summary.next_delivery_date
                                  ? `Next: ${summary.next_delivery_date}`
                                  : undefined
                        }
                        icon={RefreshCw}
                        href={subscriptionShow()}
                    />
                </div>

                {summary.next_delivery_date && (
                    <Card className="border-primary/30 bg-primary/5">
                        <CardContent className="flex items-center gap-3 py-4">
                            <Calendar className="size-5 shrink-0 text-primary" />
                            <p className="text-sm">
                                Your next scheduled delivery is on{' '}
                                <strong>{summary.next_delivery_date}</strong>.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle>Recent orders</CardTitle>
                            <CardDescription>
                                Your latest delivery history.
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
                            <div className="flex flex-col items-center gap-3 py-6 text-center">
                                <Package className="size-8 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    No orders yet. Place your first order to get
                                    started.
                                </p>
                                <Link
                                    href={ordersIndex()}
                                    className="text-sm font-medium text-primary"
                                >
                                    Browse orders →
                                </Link>
                            </div>
                        ) : (
                            recentOrders.map((order) => (
                                <Link
                                    key={order.uuid}
                                    href={orderShow(order.uuid)}
                                    className="flex flex-col gap-2 rounded-lg border p-3 transition-colors hover:border-primary/40 hover:bg-primary/5 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {order.scheduled_date}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            ₹{order.total}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={orderStatusVariant(
                                            order.status,
                                        )}
                                    >
                                        {order.status_label}
                                    </Badge>
                                </Link>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PortalDashboard.layout = {
    breadcrumbs: [{ title: 'Portal', href: dashboard() }],
};
