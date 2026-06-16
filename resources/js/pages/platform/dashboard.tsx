import { Head, Link } from '@inertiajs/react';
import { Building2, PauseCircle, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard as platformDashboard } from '@/routes/platform';
import { index as tenantsIndex, show } from '@/routes/platform/tenants';

type TenantSummary = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    plan: string | null;
    subscription_status: string | null;
    created_at: string | null;
};

type Stats = {
    total: number;
    active: number;
    suspended: number;
    closed: number;
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default' as const;
        case 'suspended':
            return 'secondary' as const;
        case 'closed':
            return 'outline' as const;
        default:
            return 'outline' as const;
    }
};

export default function PlatformDashboard({
    stats,
    recentTenants,
}: {
    stats: Stats;
    recentTenants: TenantSummary[];
}) {
    return (
        <>
            <Head title="Platform" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Platform overview"
                    description="Monitor suppliers, trials, and tenant health across Jalwala."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total tenants</CardDescription>
                            <CardTitle className="text-3xl">{stats.total}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Active</CardDescription>
                            <CardTitle className="text-3xl text-green-600">
                                {stats.active}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Suspended</CardDescription>
                            <CardTitle className="text-3xl text-amber-600">
                                {stats.suspended}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Closed</CardDescription>
                            <CardTitle className="text-3xl">{stats.closed}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle>Recent tenants</CardTitle>
                            <CardDescription>
                                Latest supplier signups and onboarded accounts.
                            </CardDescription>
                        </div>
                        <Link
                            href={tenantsIndex()}
                            className="text-sm font-medium text-primary"
                        >
                            View all →
                        </Link>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {recentTenants.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No tenants yet.
                            </p>
                        ) : (
                            recentTenants.map((tenant) => (
                                <Link
                                    key={tenant.id}
                                    href={show(tenant.slug)}
                                    className="flex flex-col gap-2 rounded-lg border p-4 transition-colors hover:border-primary/40 hover:bg-primary/5 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <p className="truncate font-medium">
                                            {tenant.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {tenant.slug}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={statusVariant(tenant.status)}>
                                            {tenant.status_label}
                                        </Badge>
                                        {tenant.plan && (
                                            <Badge variant="outline">
                                                {tenant.plan}
                                            </Badge>
                                        )}
                                    </div>
                                </Link>
                            ))
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Building2 className="size-5 text-primary" />
                                Manage tenants
                            </CardTitle>
                            <CardDescription>
                                Create suppliers, update settings, and suspend
                                accounts.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Link
                                href={tenantsIndex()}
                                className="text-sm font-medium text-primary"
                            >
                                Open tenant list →
                            </Link>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Users className="size-5 text-primary" />
                                Support impersonation
                            </CardTitle>
                            <CardDescription>
                                Enter a tenant workspace to troubleshoot without
                                sharing credentials.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <PauseCircle className="size-4 shrink-0" />
                            Use &quot;Enter as support&quot; from a tenant detail
                            page.
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

PlatformDashboard.layout = {
    breadcrumbs: [{ title: 'Platform', href: platformDashboard() }],
};
