import { Form, Head, Link } from '@inertiajs/react';
import {
    Building2,
    Eye,
    Pencil,
    PauseCircle,
    PlayCircle,
    Settings,
} from 'lucide-react';
import ImpersonationController from '@/actions/App/Http/Controllers/Platform/ImpersonationController';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
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
import { dashboard as platformDashboard } from '@/routes/platform';
import { edit, index, show } from '@/routes/platform/tenants';
import { edit as editTenantSettings } from '@/routes/platform/tenants/settings';

type TenantDetail = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    timezone: string;
    currency: string;
    plan: string | null;
    subscription_status_label: string | null;
    trial_ends_at: string | null;
    users_count: number;
    customers_count: number;
    admins: Array<{ id: number; name: string; email: string }>;
    settings: {
        branding: Record<string, string | null>;
        billing: { plan: string };
    };
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

export default function TenantShow({
    tenant,
    can,
}: {
    tenant: TenantDetail;
    can: { update: boolean; suspend: boolean; impersonate: boolean };
}) {
    const isSuspended = tenant.status === 'suspended';

    return (
        <>
            <Head title={tenant.name} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={tenant.name}
                        description={`Supplier workspace · ${tenant.slug}`}
                    />
                    <div className="flex flex-wrap gap-2">
                        {can.impersonate && (
                            <Form
                                {...ImpersonationController.store.form(
                                    tenant.slug,
                                )}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="min-h-11"
                                    >
                                        <Eye className="size-4" />
                                        Enter as support
                                    </Button>
                                )}
                            </Form>
                        )}
                        {can.update && (
                            <>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={edit(tenant.slug)}>
                                        <Pencil className="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={editTenantSettings(tenant.slug)}>
                                        <Settings className="size-4" />
                                        Settings
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant={statusVariant(tenant.status)}>
                        {tenant.status_label}
                    </Badge>
                    {tenant.plan && (
                        <Badge variant="outline">{tenant.plan}</Badge>
                    )}
                    {tenant.subscription_status_label && (
                        <Badge variant="outline">
                            {tenant.subscription_status_label}
                        </Badge>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Users</CardDescription>
                            <CardTitle className="text-2xl">
                                {tenant.users_count}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Customers</CardDescription>
                            <CardTitle className="text-2xl">
                                {tenant.customers_count}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Timezone</CardDescription>
                            <CardTitle className="text-base">
                                {tenant.timezone}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Currency</CardDescription>
                            <CardTitle className="text-base">
                                {tenant.currency}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Building2 className="size-5 text-primary" />
                            Supplier admins
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {tenant.admins.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No supplier admins assigned.
                            </p>
                        ) : (
                            tenant.admins.map((admin) => (
                                <div
                                    key={admin.id}
                                    className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <span className="font-medium">
                                        {admin.name}
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {admin.email}
                                    </span>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                {can.suspend && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Account status
                            </CardTitle>
                            <CardDescription>
                                Suspended tenants cannot access admin or portal
                                routes.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {isSuspended ? (
                                <Form
                                    {...TenantController.activate.form(
                                        tenant.slug,
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="secondary"
                                            disabled={processing}
                                            className="min-h-11"
                                        >
                                            <PlayCircle className="size-4" />
                                            Reactivate tenant
                                        </Button>
                                    )}
                                </Form>
                            ) : (
                                <Form
                                    {...TenantController.suspend.form(
                                        tenant.slug,
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                            className="min-h-11"
                                        >
                                            <PauseCircle className="size-4" />
                                            Suspend tenant
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

TenantShow.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Tenants', href: index() },
        { title: 'Details', href: show('placeholder') },
    ],
};
