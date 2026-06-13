import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import SubscriptionController from '@/actions/App/Http/Controllers/Admin/SubscriptionController';
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
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/admin/subscriptions';

type SubscriptionDetail = {
    id: number;
    status: string;
    status_label: string;
    start_date: string;
    paused_until: string | null;
    notes: string | null;
    customer: { name: string; code: string };
    address: { label: string; address_line_1: string; city: string };
    items: Array<{
        product_name: string;
        quantity: number;
        unit_price: string;
        line_total: string;
    }>;
    schedule_days: never;
    days_of_week: number[];
    pauses: Array<{
        start_date: string;
        end_date: string;
        reason: string | null;
    }>;
    weekly_total: string;
};

const formatMoney = (v: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
    }).format(Number(v));

const dayShort = (d: number) =>
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][d] ?? '?';

export default function ShowSubscription({
    subscription,
    upcomingDeliveries,
    can,
}: {
    subscription: SubscriptionDetail;
    upcomingDeliveries: string[];
    can: {
        update: boolean;
        pause: boolean;
        resume: boolean;
        cancel: boolean;
    };
}) {
    const [pauseOpen, setPauseOpen] = useState(false);

    return (
        <>
            <Head title={`Subscription · ${subscription.customer.name}`} />
            <div className="mx-auto max-w-4xl flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={subscription.customer.name}
                        description={`${subscription.customer.code} · ${formatMoney(subscription.weekly_total)} per delivery day`}
                    />
                    <Button asChild variant="outline">
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="flex flex-wrap gap-2 py-5">
                        <Badge>{subscription.status_label}</Badge>
                        {subscription.days_of_week.map((day) => (
                            <Badge key={day} variant="outline">
                                {dayShort(day)}
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2">
                    {can.update && (
                        <Button asChild variant="outline">
                            <Link href={edit(subscription.id)}>Edit</Link>
                        </Button>
                    )}
                    {can.pause && (
                        <Button onClick={() => setPauseOpen(true)}>
                            Pause
                        </Button>
                    )}
                    {can.resume && (
                        <Form
                            {...SubscriptionController.resume.form(
                                subscription.id,
                            )}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Resume
                                </Button>
                            )}
                        </Form>
                    )}
                    {can.cancel && (
                        <Form
                            {...SubscriptionController.cancel.form(
                                subscription.id,
                            )}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Cancel subscription
                                </Button>
                            )}
                        </Form>
                    )}
                </div>

                {can.pause && (
                    <Dialog open={pauseOpen} onOpenChange={setPauseOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Pause subscription</DialogTitle>
                            </DialogHeader>
                            <Form
                                {...SubscriptionController.pause.form(
                                    subscription.id,
                                )}
                                resetOnSuccess
                                onSuccess={() => setPauseOpen(false)}
                                className="space-y-4"
                            >
                                {({ processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label>Start date</Label>
                                            <Input
                                                name="start_date"
                                                type="date"
                                                required
                                                className="min-h-11"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>End date</Label>
                                            <Input
                                                name="end_date"
                                                type="date"
                                                required
                                                className="min-h-11"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Reason</Label>
                                            <Input
                                                name="reason"
                                                className="min-h-11"
                                            />
                                        </div>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="outline">
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                Pause
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Upcoming deliveries
                        </CardTitle>
                        <CardDescription>Next 14 days</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {upcomingDeliveries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No upcoming deliveries scheduled.
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {upcomingDeliveries.map((date) => (
                                    <Badge key={date} variant="secondary">
                                        {date}
                                    </Badge>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Items</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2">
                        {subscription.items.map((item, i) => (
                            <div
                                key={i}
                                className="flex justify-between text-sm"
                            >
                                <span>
                                    {item.quantity}× {item.product_name}
                                </span>
                                <span>{formatMoney(item.line_total)}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {subscription.pauses.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Pause history
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2">
                            {subscription.pauses.map((pause, i) => (
                                <p key={i} className="text-sm text-muted-foreground">
                                    {pause.start_date} → {pause.end_date}
                                    {pause.reason && ` · ${pause.reason}`}
                                </p>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

ShowSubscription.layout = {
    breadcrumbs: [{ title: 'Subscriptions', href: index() }],
};
