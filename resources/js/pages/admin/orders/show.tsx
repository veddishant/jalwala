import { Form, Head, Link } from '@inertiajs/react';
import { Calendar, CheckCircle2, MapPin, User } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { index, show } from '@/routes/admin/orders';

type OrderDetail = {
    uuid: string;
    status: string;
    status_label: string;
    source: string;
    source_label: string;
    subtotal: string;
    total: string;
    wallet_amount_charged: string;
    scheduled_date: string;
    delivered_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    created_at: string;
    created_by: string | null;
    customer: {
        id: number;
        name: string;
        code: string;
        phone: string;
    };
    address: {
        label: string;
        address_line_1: string;
        address_line_2: string | null;
        city: string;
        state: string;
        postal_code: string;
        delivery_instructions: string | null;
    };
    items: Array<{
        product_name: string;
        product_sku: string;
        quantity: number;
        unit_price: string;
        line_total: string;
    }>;
    timeline: Array<{
        from_status: string | null;
        from_status_label: string | null;
        to_status: string;
        to_status_label: string;
        notes: string | null;
        changed_by: string | null;
        created_at: string;
    }>;
};

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

const selectClassName =
    'border-input bg-background flex min-h-11 w-full rounded-md border px-3 text-sm';

function CancelOrderDialog({
    orderUuid,
    open,
    onOpenChange,
}: {
    orderUuid: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel order</DialogTitle>
                    <DialogDescription>
                        This will cancel the order and refund any wallet charge.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OrderController.cancel.form(orderUuid)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
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
                                {errors.cancellation_reason && (
                                    <p className="text-sm text-destructive">
                                        {errors.cancellation_reason}
                                    </p>
                                )}
                            </div>
                            <DialogFooter className="gap-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
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
                                    {processing ? 'Cancelling…' : 'Cancel order'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ShowOrder({
    order,
    nextStatuses,
    can,
}: {
    order: OrderDetail;
    nextStatuses: Array<{ value: string; label: string }>;
    can: { confirm: boolean; cancel: boolean; transition: boolean };
}) {
    const [cancelOpen, setCancelOpen] = useState(false);
    const [transitionStatus, setTransitionStatus] = useState(
        nextStatuses[0]?.value ?? '',
    );

    return (
        <>
            <Head title={`Order ${order.uuid.slice(0, 8)}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`Order for ${order.customer.name}`}
                        description={`${order.customer.code} · ${order.source_label}`}
                    />
                    <Button asChild variant="outline" className="min-h-10">
                        <Link href={index()}>Back to orders</Link>
                    </Button>
                </div>

                <Card className="overflow-hidden border-primary/20">
                    <div className="bg-primary/5 px-6 py-6">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge>{order.status_label}</Badge>
                            <span className="text-3xl font-semibold">
                                {formatMoney(order.total)}
                            </span>
                        </div>
                        <div className="mt-3 flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <span className="flex items-center gap-1">
                                <Calendar className="size-4" />
                                {order.scheduled_date}
                            </span>
                            {Number(order.wallet_amount_charged) > 0 && (
                                <span>
                                    Wallet charged{' '}
                                    {formatMoney(order.wallet_amount_charged)}
                                </span>
                            )}
                        </div>
                    </div>

                    {(can.confirm || can.cancel || can.transition) && (
                        <>
                            <Separator />
                            <CardContent className="flex flex-wrap gap-2 px-6 py-4">
                                {can.confirm && (
                                    <Form
                                        {...OrderController.confirm.form(
                                            order.uuid,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="min-h-10"
                                            >
                                                {processing && <Spinner />}
                                                <CheckCircle2 className="size-4" />
                                                Confirm order
                                            </Button>
                                        )}
                                    </Form>
                                )}
                                {can.transition && nextStatuses.length > 0 && (
                                    <Form
                                        {...OrderController.transition.form(
                                            order.uuid,
                                        )}
                                        options={{ preserveScroll: true }}
                                        className="flex flex-wrap items-end gap-2"
                                    >
                                        {({ processing }) => (
                                            <>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="status">
                                                        Advance status
                                                    </Label>
                                                    <select
                                                        id="status"
                                                        name="status"
                                                        className={selectClassName}
                                                        value={transitionStatus}
                                                        onChange={(event) =>
                                                            setTransitionStatus(
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    >
                                                        {nextStatuses.map(
                                                            (status) => (
                                                                <option
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    value={
                                                                        status.value
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </div>
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    disabled={processing}
                                                    className="min-h-11"
                                                >
                                                    {processing && <Spinner />}
                                                    Update status
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                )}
                                {can.cancel && (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="min-h-10"
                                        onClick={() => setCancelOpen(true)}
                                    >
                                        Cancel order
                                    </Button>
                                )}
                            </CardContent>
                        </>
                    )}
                </Card>

                {can.cancel && (
                    <CancelOrderDialog
                        orderUuid={order.uuid}
                        open={cancelOpen}
                        onOpenChange={setCancelOpen}
                    />
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Customer</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p className="flex items-center gap-2 font-medium">
                                <User className="size-4" />
                                {order.customer.name}
                            </p>
                            <p className="text-muted-foreground">
                                {order.customer.code} · {order.customer.phone}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Delivery address
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p className="flex items-center gap-2 font-medium">
                                <MapPin className="size-4" />
                                {order.address.label}
                            </p>
                            <p>{order.address.address_line_1}</p>
                            <p className="text-muted-foreground">
                                {order.address.city}, {order.address.state}{' '}
                                {order.address.postal_code}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Line items</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {order.items.map((item, index) => (
                            <div
                                key={`${item.product_sku}-${index}`}
                                className="flex justify-between rounded-lg border px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium">
                                        {item.product_name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {item.quantity}× @{' '}
                                        {formatMoney(item.unit_price)}
                                    </p>
                                </div>
                                <p className="font-medium">
                                    {formatMoney(item.line_total)}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Status timeline
                        </CardTitle>
                        <CardDescription>
                            Full audit trail of status changes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {order.timeline.map((entry, index) => (
                            <div
                                key={`${entry.created_at}-${index}`}
                                className="relative border-l-2 border-primary/30 pl-4"
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
                                    {entry.changed_by &&
                                        ` · ${entry.changed_by}`}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ShowOrder.layout = {
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: 'Details', href: show('00000000-0000-0000-0000-000000000000') },
    ],
};
