import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Plus, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/admin/subscriptions';

type SubscriptionSummary = {
    id: number;
    customer_name: string;
    customer_code: string;
    status: string;
    status_label: string;
    start_date: string;
    schedule_days: string;
    weekly_total: string;
};

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function SubscriptionsIndex({
    subscriptions,
    filters,
    statuses,
    can,
}: {
    subscriptions: {
        data: SubscriptionSummary[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        last_page: number;
    };
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
            <Head title="Subscriptions" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Subscriptions"
                        description="Recurring delivery plans with weekly schedules."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                New subscription
                            </Link>
                        </Button>
                    )}
                </div>

                <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search customer"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" className="min-h-11">
                        Filter
                    </Button>
                </form>

                {subscriptions.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            No subscriptions found.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3">
                        {subscriptions.data.map((sub) => (
                            <Card key={sub.id}>
                                <CardContent className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {sub.customer_name}
                                            </p>
                                            <Badge variant="outline">
                                                {sub.customer_code}
                                            </Badge>
                                            <Badge>{sub.status_label}</Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            <Calendar className="mr-1 inline size-3.5" />
                                            {sub.schedule_days} · from{' '}
                                            {sub.start_date}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <p className="font-semibold">
                                            {formatMoney(sub.weekly_total)}/wk
                                        </p>
                                        <Button asChild variant="outline">
                                            <Link href={show(sub.id)}>View</Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

SubscriptionsIndex.layout = {
    breadcrumbs: [{ title: 'Subscriptions', href: index() }],
};
