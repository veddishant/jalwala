import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Portal/OrderController';
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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes/portal';
import { index, show } from '@/routes/portal/orders';

type OrderDetail = {
    uuid: string;
    status: string;
    status_label: string;
    total: string;
    wallet_amount_charged: string;
    scheduled_date: string;
    notes: string | null;
    cancellation_reason: string | null;
    created_at: string;
    address: {
        label: string;
        address_line_1: string;
        city: string;
        postal_code: string;
    };
    items: Array<{
        product_name: string;
        quantity: number;
        unit_price: string;
        line_total: string;
    }>;
    timeline: Array<{
        to_status: string;
        to_status_label: string;
        notes: string | null;
        created_at: string;
    }>;
};

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function PortalShowOrder({
    order,
    can,
}: {
    order: OrderDetail;
    can: { cancel: boolean };
}) {
    const [cancelOpen, setCancelOpen] = useState(false);

    return (
        <>
            <Head title="Order details" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Order details"
                    description={`Delivery on ${order.scheduled_date}`}
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex flex-wrap items-center gap-3 py-5">
                        <Badge>{order.status_label}</Badge>
                        <span className="text-2xl font-semibold">
                            {formatMoney(order.total)}
                        </span>
                        {Number(order.wallet_amount_charged) > 0 && (
                            <span className="text-sm text-muted-foreground">
                                Charged from wallet
                            </span>
                        )}
                    </CardContent>
                </Card>

                {can.cancel && (
                    <>
                        <Button
                            type="button"
                            variant="destructive"
                            className="min-h-11 w-full sm:w-auto"
                            onClick={() => setCancelOpen(true)}
                        >
                            Cancel order
                        </Button>
                        <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Cancel order</DialogTitle>
                                    <DialogDescription>
                                        Your wallet will be refunded if this
                                        order was already charged.
                                    </DialogDescription>
                                </DialogHeader>
                                <Form
                                    {...OrderController.cancel.form(order.uuid)}
                                    options={{ preserveScroll: true }}
                                    resetOnSuccess
                                    onSuccess={() => setCancelOpen(false)}
                                    className="space-y-4"
                                >
                                    {({ processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="cancellation_reason">
                                                    Reason (optional)
                                                </Label>
                                                <Input
                                                    id="cancellation_reason"
                                                    name="cancellation_reason"
                                                    disabled={processing}
                                                    className="min-h-11"
                                                />
                                            </div>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                    >
                                                        Keep order
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    type="submit"
                                                    variant="destructive"
                                                    disabled={processing}
                                                >
                                                    {processing && <Spinner />}
                                                    Cancel order
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Items</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {order.items.map((item, index) => (
                            <div
                                key={`${item.product_name}-${index}`}
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

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Delivery</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm">
                        <p className="font-medium">{order.address.label}</p>
                        <p>{order.address.address_line_1}</p>
                        <p className="text-muted-foreground">
                            {order.address.city} {order.address.postal_code}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Timeline</CardTitle>
                        <CardDescription>Order status history</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {order.timeline.map((entry, index) => (
                            <div
                                key={`${entry.created_at}-${index}`}
                                className="border-l-2 border-primary/30 pl-4"
                            >
                                <p className="font-medium">
                                    {entry.to_status_label}
                                </p>
                                {entry.notes && (
                                    <p className="text-sm text-muted-foreground">
                                        {entry.notes}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    {new Date(
                                        entry.created_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Button asChild variant="outline" className="min-h-11">
                    <Link href={index()}>Back to orders</Link>
                </Button>
            </div>
        </>
    );
}

PortalShowOrder.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Orders', href: index() },
        { title: 'Details', href: show('00000000-0000-0000-0000-000000000000') },
    ],
};
