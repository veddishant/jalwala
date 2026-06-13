import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Plus, Search, ShoppingBag } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/admin/orders';

type OrderSummary = {
    uuid: string;
    customer_name: string;
    customer_code: string;
    status: string;
    status_label: string;
    source: string;
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

const statusVariant = (status: string) => {
    switch (status) {
        case 'completed':
        case 'delivered':
            return 'default' as const;
        case 'cancelled':
        case 'failed':
            return 'destructive' as const;
        case 'pending':
        case 'assigned':
        case 'out_for_delivery':
            return 'secondary' as const;
        default:
            return 'outline' as const;
    }
};

export default function OrdersIndex({
    orders,
    filters,
    statuses,
    can,
}: {
    orders: PaginatedOrders;
    filters: { search: string; status: string };
    statuses: Array<{ value: string; label: string }>;
    can: { create: boolean };
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            index.url({ query: { search, status: status || undefined } }),
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Orders"
                        description="Manage manual orders, track status, and process deliveries."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                New order
                            </Link>
                        </Button>
                    )}
                </div>

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 sm:flex-row"
                >
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by customer name or code"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="border-input bg-background flex min-h-11 rounded-md border px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        {statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" className="min-h-11">
                        Filter
                    </Button>
                </form>

                {orders.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            No orders found.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3">
                        {orders.data.map((order) => (
                            <Card key={order.uuid}>
                                <CardContent className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {order.customer_name}
                                            </p>
                                            <Badge variant="outline">
                                                {order.customer_code}
                                            </Badge>
                                            <Badge
                                                variant={statusVariant(
                                                    order.status,
                                                )}
                                            >
                                                {order.status_label}
                                            </Badge>
                                        </div>
                                        <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                            <span className="flex items-center gap-1">
                                                <Calendar className="size-3.5" />
                                                {order.scheduled_date}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <ShoppingBag className="size-3.5" />
                                                {order.item_count} item
                                                {order.item_count === 1
                                                    ? ''
                                                    : 's'}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <p className="text-lg font-semibold">
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
                                    className="min-h-9"
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
                                    className="min-h-9"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [{ title: 'Orders', href: index() }],
};
