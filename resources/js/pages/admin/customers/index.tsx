import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Search, UserRound } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/admin/customers';

type ManagedCustomer = {
    id: number;
    code: string;
    name: string;
    phone: string;
    email: string | null;
    status: string;
    has_portal_account: boolean;
};

type PaginatedCustomers = {
    data: ManagedCustomer[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'secondary' as const;
        case 'paused':
            return 'outline' as const;
        case 'prospect':
            return 'default' as const;
        default:
            return 'destructive' as const;
    }
};

export default function CustomersIndex({
    customers,
    filters,
    statuses,
    can,
}: {
    customers: PaginatedCustomers;
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
            <Head title="Customers" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Customers"
                        description="Manage customer accounts, delivery addresses, and portal access."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Add customer
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
                            placeholder="Search by name, phone, email, or code"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="border-input bg-background flex min-h-11 rounded-md border px-3 py-2 text-sm shadow-xs"
                    >
                        <option value="">All statuses</option>
                        {statuses.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" className="min-h-11">
                        Filter
                    </Button>
                </form>

                {customers.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <UserRound className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                No customers found. Add your first customer to
                                get started.
                            </p>
                            {can.create && (
                                <Button asChild>
                                    <Link href={create()}>Add customer</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {customers.data.map((customer) => (
                            <Card key={customer.id}>
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-3">
                                    <div className="min-w-0 space-y-1">
                                        <CardTitle className="truncate text-base">
                                            {customer.name}
                                        </CardTitle>
                                        <CardDescription className="truncate">
                                            {customer.code} · {customer.phone}
                                        </CardDescription>
                                    </div>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="min-h-9 shrink-0"
                                    >
                                        <Link href={edit(customer.id)}>
                                            <Pencil className="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center gap-2 pt-0">
                                    <Badge variant={statusVariant(customer.status)}>
                                        {customer.status}
                                    </Badge>
                                    {customer.has_portal_account && (
                                        <Badge variant="outline">Portal</Badge>
                                    )}
                                    {customer.email && (
                                        <span className="text-sm text-muted-foreground">
                                            {customer.email}
                                        </span>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {customers.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {customers.links.map((link, linkIndex) =>
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

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Customers', href: index() }],
};
