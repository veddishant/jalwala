import { Form, Head, Link } from '@inertiajs/react';
import { Minus, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import SubscriptionController from '@/actions/App/Http/Controllers/Admin/SubscriptionController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, show } from '@/routes/admin/subscriptions';

export default function EditSubscription({
    subscription,
    products,
    daysOfWeek,
}: {
    subscription: {
        id: number;
        customer: { name: string; code: string };
        address: { id: number };
        items: Array<{ product_id: number; quantity: number }>;
        days_of_week: number[];
        notes: string | null;
    };
    products: Array<{ id: number; name: string }>;
    daysOfWeek: Array<{ value: number; short: string }>;
}) {
    const [selectedDays, setSelectedDays] = useState(subscription.days_of_week);
    const [quantities, setQuantities] = useState<Record<number, number>>(
        Object.fromEntries(
            subscription.items.map((i) => [i.product_id, i.quantity]),
        ),
    );

    const selectedItems = useMemo(
        () =>
            products
                .filter((p) => (quantities[p.id] ?? 0) > 0)
                .map((p) => ({ product: p, quantity: quantities[p.id] })),
        [products, quantities],
    );

    const toggleDay = (day: number) => {
        setSelectedDays((current) =>
            current.includes(day)
                ? current.filter((d) => d !== day)
                : [...current, day].sort(),
        );
    };

    return (
        <>
            <Head title={`Edit · ${subscription.customer.name}`} />
            <div className="mx-auto max-w-3xl p-4 md:p-6">
                <Heading
                    title={`Edit ${subscription.customer.name}`}
                    description={subscription.customer.code}
                />
                <Form
                    {...SubscriptionController.update.form(subscription.id)}
                    className="mt-6 space-y-6"
                >
                    {({ processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="customer_address_id"
                                value={subscription.address.id}
                            />
                            {selectedDays.map((day) => (
                                <input
                                    key={day}
                                    type="hidden"
                                    name="days_of_week[]"
                                    value={day}
                                />
                            ))}
                            {selectedItems.map((item, index) => (
                                <div key={item.product.id}>
                                    <input
                                        type="hidden"
                                        name={`items[${index}][product_id]`}
                                        value={item.product.id}
                                    />
                                    <input
                                        type="hidden"
                                        name={`items[${index}][quantity]`}
                                        value={item.quantity}
                                    />
                                </div>
                            ))}

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Delivery days
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-wrap gap-2">
                                    {daysOfWeek.map((day) => (
                                        <Button
                                            key={day.value}
                                            type="button"
                                            size="sm"
                                            variant={
                                                selectedDays.includes(day.value)
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            onClick={() => toggleDay(day.value)}
                                        >
                                            {day.short}
                                        </Button>
                                    ))}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Products
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-3">
                                    {products.map((product) => {
                                        const qty = quantities[product.id] ?? 0;
                                        return (
                                            <div
                                                key={product.id}
                                                className="flex items-center justify-between border px-4 py-3 rounded-lg"
                                            >
                                                <span>{product.name}</span>
                                                <div className="flex gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-9"
                                                        onClick={() =>
                                                            setQuantities(
                                                                (c) => ({
                                                                    ...c,
                                                                    [product.id]:
                                                                        Math.max(
                                                                            0,
                                                                            qty -
                                                                                1,
                                                                        ),
                                                                }),
                                                            )
                                                        }
                                                    >
                                                        <Minus className="size-4" />
                                                    </Button>
                                                    <span className="min-w-8 text-center">
                                                        {qty}
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-9"
                                                        onClick={() =>
                                                            setQuantities(
                                                                (c) => ({
                                                                    ...c,
                                                                    [product.id]:
                                                                        qty + 1,
                                                                }),
                                                            )
                                                        }
                                                    >
                                                        <Plus className="size-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </CardContent>
                            </Card>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Input
                                    id="notes"
                                    name="notes"
                                    defaultValue={subscription.notes ?? ''}
                                    className="min-h-11"
                                />
                            </div>

                            <div className="flex gap-2">
                                <Button asChild variant="outline">
                                    <Link href={show(subscription.id)}>
                                        Cancel
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        selectedDays.length === 0 ||
                                        selectedItems.length === 0
                                    }
                                >
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditSubscription.layout = {
    breadcrumbs: [{ title: 'Subscriptions', href: index() }],
};
