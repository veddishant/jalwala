import { Form, Head, Link } from '@inertiajs/react';
import { Calendar, Pause, Play } from 'lucide-react';
import { useState } from 'react';
import SubscriptionController from '@/actions/App/Http/Controllers/Portal/SubscriptionController';
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
import { dashboard } from '@/routes/portal';
import { show } from '@/routes/portal/subscription';

type SubscriptionInfo = {
    id: number;
    status: string;
    status_label: string;
    start_date: string;
    paused_until: string | null;
    address: { label: string; address_line_1: string; city: string };
    items: Array<{
        product_name: string;
        quantity: number;
        unit_price: string;
    }>;
    schedule_days: string[];
    pauses: Array<{
        start_date: string;
        end_date: string;
        reason: string | null;
    }>;
};

export default function PortalSubscription({
    subscription,
    upcomingDeliveries,
    can,
}: {
    subscription: SubscriptionInfo | null;
    upcomingDeliveries: string[];
    can: { pause: boolean; resume: boolean };
}) {
    const [pauseOpen, setPauseOpen] = useState(false);

    if (subscription === null) {
        return (
            <>
                <Head title="Subscription" />
                <div className="p-4 md:p-6">
                    <Heading
                        title="Subscription"
                        description="You don't have an active subscription yet."
                    />
                    <Card className="mt-6">
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            Contact your supplier to set up recurring
                            deliveries.
                        </CardContent>
                    </Card>
                    <Button asChild variant="outline" className="mt-4 min-h-11">
                        <Link href={dashboard()}>Back to portal</Link>
                    </Button>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Subscription" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="My subscription"
                    description="Recurring delivery schedule and vacation pauses."
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex flex-wrap items-center gap-2 py-5">
                        <Badge>{subscription.status_label}</Badge>
                        {subscription.schedule_days.map((day) => (
                            <Badge key={day} variant="outline">
                                {day}
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2">
                    {can.pause && (
                        <Button
                            className="min-h-11"
                            onClick={() => setPauseOpen(true)}
                        >
                            <Pause className="size-4" />
                            Pause (vacation)
                        </Button>
                    )}
                    {can.resume && (
                        <Form
                            {...SubscriptionController.resume.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="min-h-11"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    <Play className="size-4" />
                                    Resume deliveries
                                </Button>
                            )}
                        </Form>
                    )}
                </div>

                {can.pause && (
                    <Dialog open={pauseOpen} onOpenChange={setPauseOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Vacation pause</DialogTitle>
                            </DialogHeader>
                            <Form
                                {...SubscriptionController.pause.form()}
                                resetOnSuccess
                                onSuccess={() => setPauseOpen(false)}
                                className="space-y-4"
                            >
                                {({ processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label>From</Label>
                                            <Input
                                                name="start_date"
                                                type="date"
                                                required
                                                className="min-h-11"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Until</Label>
                                            <Input
                                                name="end_date"
                                                type="date"
                                                required
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
                        <CardTitle className="text-base flex items-center gap-2">
                            <Calendar className="size-4" />
                            Upcoming deliveries
                        </CardTitle>
                        <CardDescription>Next 14 days</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {upcomingDeliveries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No deliveries in the next two weeks.
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
                        <CardTitle className="text-base">
                            Delivery details
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <p>
                            {subscription.address.label} —{' '}
                            {subscription.address.address_line_1},{' '}
                            {subscription.address.city}
                        </p>
                        {subscription.items.map((item, i) => (
                            <p key={i}>
                                {item.quantity}× {item.product_name}
                            </p>
                        ))}
                    </CardContent>
                </Card>

                <Button asChild variant="outline" className="min-h-11 w-full sm:w-auto">
                    <Link href={dashboard()}>Back to portal</Link>
                </Button>
            </div>
        </>
    );
}

PortalSubscription.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Subscription', href: show() },
    ],
};
