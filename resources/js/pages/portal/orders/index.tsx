import { Head, Link } from '@inertiajs/react';
import { Calendar, Plus, ShoppingBag } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes/portal';
import { create, index, show } from '@/routes/portal/orders';

type OrderSummary = {
    uuid: string;
    status: string;
    status_label: string;
    total: string;
    scheduled_date: string;
    item_count: number;
    created_at: string;
};

type PaginatedOrders = {
    data: OrderSummary[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function PortalOrdersIndex({
    orders,
    can,
}: {
    orders: PaginatedOrders;
    can: { create: boolean };
}) {
    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="My orders"
                        description="View order history and place new delivery requests."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Place order
                            </Link>
                        </Button>
                    )}
                </div>

                {orders.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            No orders yet.
                            {can.create && ' Place your first delivery order.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3">
                        {orders.data.map((order) => (
                            <Card key={order.uuid}>
                                <CardContent className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge>{order.status_label}</Badge>
                                            <span className="flex items-center gap-1 text-sm text-muted-foreground">
                                                <Calendar className="size-3.5" />
                                                {order.scheduled_date}
                                            </span>
                                        </div>
                                        <p className="flex items-center gap-1 text-sm text-muted-foreground">
                                            <ShoppingBag className="size-3.5" />
                                            {order.item_count} item
                                            {order.item_count === 1 ? '' : 's'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <p className="font-semibold">
                                            {formatMoney(order.total)}
                                        </p>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="min-h-10"
                                        >
                                            <Link href={show(order.uuid)}>
                                                View
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {orders.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {orders.links.map((link, linkIndex) =>
                            link.url ? (
                                <Button
                                    key={`${link.label}-${linkIndex}`}
                                    asChild
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                >
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={`${link.label}-${linkIndex}`}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}

                <Button asChild variant="outline" className="min-h-11 w-full sm:w-auto">
                    <Link href={dashboard()}>Back to portal</Link>
                </Button>
            </div>
        </>
    );
}

PortalOrdersIndex.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Orders', href: index() },
    ],
};
