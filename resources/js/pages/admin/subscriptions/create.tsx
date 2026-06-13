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
import { create, index } from '@/routes/admin/subscriptions';

type DayOption = { value: number; label: string; short: string };

const selectClassName =
    'border-input bg-background flex min-h-11 w-full rounded-md border px-3 text-sm';

export default function CreateSubscription({
    customers,
    products,
    daysOfWeek,
}: {
    customers: Array<{
        id: number;
        name: string;
        code: string;
        addresses: Array<{
            id: number;
            label: string;
            address_line_1: string;
            city: string;
            is_default: boolean;
        }>;
    }>;
    products: Array<{
        id: number;
        name: string;
        sku: string;
        unit_price: string;
    }>;
    daysOfWeek: DayOption[];
}) {
    const [customerId, setCustomerId] = useState(
        customers[0]?.id?.toString() ?? '',
    );
    const [addressId, setAddressId] = useState('');
    const [selectedDays, setSelectedDays] = useState<number[]>([1, 3, 5]);
    const [quantities, setQuantities] = useState<Record<number, number>>({});

    const selectedCustomer = customers.find(
        (c) => c.id.toString() === customerId,
    );
    const defaultAddress =
        selectedCustomer?.addresses.find((a) => a.is_default)?.id ??
        selectedCustomer?.addresses[0]?.id;
    const effectiveAddressId = addressId || defaultAddress?.toString() || '';

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
            <Head title="New subscription" />
            <div className="mx-auto max-w-3xl flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Create subscription"
                    description="Set up a recurring weekly delivery plan."
                />
                <Form {...SubscriptionController.store.form()}>
                    {({ processing, errors }) => (
                        <div className="space-y-6">
                            <input
                                type="hidden"
                                name="customer_id"
                                value={customerId}
                            />
                            <input
                                type="hidden"
                                name="customer_address_id"
                                value={effectiveAddressId}
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
                                        Customer
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <select
                                        className={selectClassName}
                                        value={customerId}
                                        onChange={(e) => {
                                            setCustomerId(e.target.value);
                                            setAddressId('');
                                        }}
                                    >
                                        {customers.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} ({c.code})
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        className={selectClassName}
                                        value={effectiveAddressId}
                                        onChange={(e) =>
                                            setAddressId(e.target.value)
                                        }
                                    >
                                        {selectedCustomer?.addresses.map(
                                            (a) => (
                                                <option key={a.id} value={a.id}>
                                                    {a.label} — {a.city}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                    <div className="grid gap-2">
                                        <Label htmlFor="start_date">
                                            Start date
                                        </Label>
                                        <Input
                                            id="start_date"
                                            name="start_date"
                                            type="date"
                                            required
                                            className="min-h-11"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Delivery days
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {daysOfWeek.map((day) => (
                                            <Button
                                                key={day.value}
                                                type="button"
                                                size="sm"
                                                variant={
                                                    selectedDays.includes(
                                                        day.value,
                                                    )
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="min-h-10 min-w-14"
                                                onClick={() =>
                                                    toggleDay(day.value)
                                                }
                                            >
                                                {day.short}
                                            </Button>
                                        ))}
                                    </div>
                                    {errors.days_of_week && (
                                        <p className="mt-2 text-sm text-destructive">
                                            {errors.days_of_week}
                                        </p>
                                    )}
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
                                                className="flex items-center justify-between rounded-lg border px-4 py-3"
                                            >
                                                <p className="font-medium">
                                                    {product.name}
                                                </p>
                                                <div className="flex items-center gap-2">
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
                                                                            (c[
                                                                                product
                                                                                    .id
                                                                            ] ??
                                                                                0) -
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
                                                                        (c[
                                                                            product
                                                                                .id
                                                                        ] ??
                                                                            0) +
                                                                        1,
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
                                    {errors.items && (
                                        <p className="text-sm text-destructive">
                                            {errors.items}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button asChild variant="outline">
                                    <Link href={index()}>Cancel</Link>
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
                                    Create subscription
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateSubscription.layout = {
    breadcrumbs: [
        { title: 'Subscriptions', href: index() },
        { title: 'Create', href: create() },
    ],
};
