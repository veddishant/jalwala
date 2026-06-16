import { Head } from '@inertiajs/react';
import { CheckCircle2, Package, Truck } from 'lucide-react';
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
import { dashboard } from '@/routes/agent';

type Stats = {
    today_deliveries: number;
    out_for_delivery: number;
    delivered_today: number;
    pending_pickup: number;
};

type Delivery = {
    uuid: string;
    customer_name: string | null;
    customer_phone: string | null;
    address: string | null;
    status: string;
    status_label: string;
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'out_for_delivery':
            return 'default' as const;
        case 'assigned':
            return 'secondary' as const;
        default:
            return 'outline' as const;
    }
};

export default function AgentDashboard({
    tenant,
    stats,
    todayDeliveries,
}: {
    tenant: { name: string };
    stats: Stats;
    todayDeliveries: Delivery[];
}) {
    return (
        <>
            <Head title="Deliveries" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Today's route"
                    description={`${tenant.name} · Assigned deliveries and progress.`}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Scheduled today"
                        value={stats.today_deliveries}
                        icon={Package}
                    />
                    <StatCard
                        label="Out for delivery"
                        value={stats.out_for_delivery}
                        icon={Truck}
                        valueClassName={
                            stats.out_for_delivery > 0
                                ? 'text-primary'
                                : undefined
                        }
                    />
                    <StatCard
                        label="Delivered today"
                        value={stats.delivered_today}
                        icon={CheckCircle2}
                        valueClassName="text-green-600"
                    />
                    <StatCard
                        label="Awaiting pickup"
                        value={stats.pending_pickup}
                        description="Assigned, not yet out"
                        icon={Truck}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Today's deliveries</CardTitle>
                        <CardDescription>
                            Customers scheduled for delivery today.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {todayDeliveries.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No deliveries scheduled for today.
                            </p>
                        ) : (
                            todayDeliveries.map((delivery) => (
                                <div
                                    key={delivery.uuid}
                                    className="flex flex-col gap-2 rounded-lg border p-4 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <p className="font-medium">
                                            {delivery.customer_name}
                                        </p>
                                        {delivery.address && (
                                            <p className="text-sm text-muted-foreground">
                                                {delivery.address}
                                            </p>
                                        )}
                                        {delivery.customer_phone && (
                                            <p className="text-sm text-muted-foreground">
                                                {delivery.customer_phone}
                                            </p>
                                        )}
                                    </div>
                                    <Badge
                                        variant={statusVariant(
                                            delivery.status,
                                        )}
                                        className="shrink-0"
                                    >
                                        {delivery.status_label}
                                    </Badge>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AgentDashboard.layout = {
    breadcrumbs: [{ title: 'Deliveries', href: dashboard() }],
};
