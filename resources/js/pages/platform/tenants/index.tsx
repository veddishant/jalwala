import { Form, Head, Link } from '@inertiajs/react';
import { Building2, Eye, Plus, Search } from 'lucide-react';
import ImpersonationController from '@/actions/App/Http/Controllers/Platform/ImpersonationController';
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
import { dashboard as platformDashboard } from '@/routes/platform';
import { create, index, show } from '@/routes/platform/tenants';

type TenantListItem = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    plan: string | null;
    subscription_status_label: string | null;
    users_count: number;
    customers_count: number;
    created_at: string | null;
    can: { impersonate: boolean };
};

type PaginatedTenants = {
    data: TenantListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default' as const;
        case 'suspended':
            return 'secondary' as const;
        default:
            return 'outline' as const;
    }
};

export default function TenantsIndex({
    tenants,
    filters,
    can,
}: {
    tenants: PaginatedTenants;
    filters: { search: string };
    can: { create: boolean };
}) {
    return (
        <>
            <Head title="Tenants" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Tenants"
                        description="All supplier organizations on the platform."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Add tenant
                            </Link>
                        </Button>
                    )}
                </div>

                <Form action={index().url} method="get" className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search by name or slug"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary" className="min-h-11">
                        Search
                    </Button>
                </Form>

                {tenants.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Building2 className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                No tenants found.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {tenants.data.map((tenant) => (
                            <Card key={tenant.id}>
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-3">
                                    <div className="min-w-0 space-y-1">
                                        <CardTitle className="truncate text-base">
                                            {tenant.name}
                                        </CardTitle>
                                        <CardDescription className="truncate">
                                            {tenant.slug}
                                        </CardDescription>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        {tenant.can.impersonate && (
                                            <Form
                                                {...ImpersonationController.store.form(
                                                    tenant.slug,
                                                )}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={processing}
                                                        className="min-h-9"
                                                    >
                                                        <Eye className="size-4" />
                                                        Support
                                                    </Button>
                                                )}
                                            </Form>
                                        )}
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="min-h-9"
                                        >
                                            <Link href={show(tenant.slug)}>
                                                View
                                            </Link>
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center gap-2 pt-0">
                                    <Badge variant={statusVariant(tenant.status)}>
                                        {tenant.status_label}
                                    </Badge>
                                    {tenant.plan && (
                                        <Badge variant="outline">
                                            {tenant.plan}
                                        </Badge>
                                    )}
                                    <span className="text-sm text-muted-foreground">
                                        {tenant.users_count} users ·{' '}
                                        {tenant.customers_count} customers
                                    </span>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

TenantsIndex.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Tenants', href: index() },
    ],
};
