import { Form, Head, Link } from '@inertiajs/react';
import { Minus, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/admin/orders';

type CustomerOption = {
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
};

type ProductOption = {
    id: number;
    name: string;
    sku: string;
    unit_price: string;
    capacity_liters: string | null;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function CreateOrder({
    customers,
    products,
}: {
    customers: CustomerOption[];
    products: ProductOption[];
}) {
    const [step, setStep] = useState(1);
    const [customerId, setCustomerId] = useState(
        customers[0]?.id?.toString() ?? '',
    );
    const [addressId, setAddressId] = useState('');
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const [scheduledDate, setScheduledDate] = useState('');
    const [notes, setNotes] = useState('');

    const selectedCustomer = useMemo(
        () => customers.find((c) => c.id.toString() === customerId),
        [customers, customerId],
    );

    const selectedItems = useMemo(
        () =>
            products
                .filter((product) => (quantities[product.id] ?? 0) > 0)
                .map((product) => ({
                    product,
                    quantity: quantities[product.id],
                })),
        [products, quantities],
    );

    const estimatedTotal = useMemo(
        () =>
            selectedItems.reduce(
                (sum, item) =>
                    sum + Number(item.product.unit_price) * item.quantity,
                0,
            ),
        [selectedItems],
    );

    const defaultAddressId =
        selectedCustomer?.addresses.find((a) => a.is_default)?.id ??
        selectedCustomer?.addresses[0]?.id;

    const effectiveAddressId = addressId || defaultAddressId?.toString() || '';

    const adjustQuantity = (productId: number, delta: number) => {
        setQuantities((current) => {
            const next = Math.max(0, (current[productId] ?? 0) + delta);

            return { ...current, [productId]: next };
        });
    };

    const canProceedStep1 = customerId !== '' && effectiveAddressId !== '';
    const canProceedStep2 = selectedItems.length > 0;
    const canSubmit = scheduledDate !== '';

    return (
        <>
            <Head title="New order" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Create order"
                    description="Build a draft order — confirm it later to charge the customer's wallet."
                />

                <div className="flex gap-2">
                    {[1, 2, 3].map((stepNumber) => (
                        <div
                            key={stepNumber}
                            className={`flex-1 rounded-lg border px-3 py-2 text-center text-sm ${
                                step === stepNumber
                                    ? 'border-primary bg-primary/5 font-medium'
                                    : 'text-muted-foreground'
                            }`}
                        >
                            {stepNumber === 1 && 'Customer'}
                            {stepNumber === 2 && 'Products'}
                            {stepNumber === 3 && 'Schedule'}
                        </div>
                    ))}
                </div>

                <Form
                    {...OrderController.store.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
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
                            <input
                                type="hidden"
                                name="scheduled_date"
                                value={scheduledDate}
                            />
                            <input type="hidden" name="notes" value={notes} />

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

                            {step === 1 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Customer & address</CardTitle>
                                        <CardDescription>
                                            Choose who this delivery is for.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="customer_id">
                                                Customer
                                            </Label>
                                            <select
                                                id="customer_id"
                                                className={selectClassName}
                                                value={customerId}
                                                onChange={(event) => {
                                                    setCustomerId(
                                                        event.target.value,
                                                    );
                                                    setAddressId('');
                                                }}
                                            >
                                                {customers.map((customer) => (
                                                    <option
                                                        key={customer.id}
                                                        value={customer.id}
                                                    >
                                                        {customer.name} (
                                                        {customer.code})
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.customer_id && (
                                                <p className="text-sm text-destructive">
                                                    {errors.customer_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="customer_address_id">
                                                Delivery address
                                            </Label>
                                            <select
                                                id="customer_address_id"
                                                className={selectClassName}
                                                value={effectiveAddressId}
                                                onChange={(event) =>
                                                    setAddressId(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                {selectedCustomer?.addresses.map(
                                                    (address) => (
                                                        <option
                                                            key={address.id}
                                                            value={address.id}
                                                        >
                                                            {address.label} —{' '}
                                                            {
                                                                address.address_line_1
                                                            }
                                                            , {address.city}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            {errors.customer_address_id && (
                                                <p className="text-sm text-destructive">
                                                    {
                                                        errors.customer_address_id
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {step === 2 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Products</CardTitle>
                                        <CardDescription>
                                            Add quantities for each product.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="grid gap-3">
                                        {products.map((product) => {
                                            const qty =
                                                quantities[product.id] ?? 0;

                                            return (
                                                <div
                                                    key={product.id}
                                                    className="flex items-center justify-between rounded-lg border px-4 py-3"
                                                >
                                                    <div>
                                                        <p className="font-medium">
                                                            {product.name}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {product.sku} ·{' '}
                                                            {formatMoney(
                                                                product.unit_price,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            className="size-9"
                                                            onClick={() =>
                                                                adjustQuantity(
                                                                    product.id,
                                                                    -1,
                                                                )
                                                            }
                                                        >
                                                            <Minus className="size-4" />
                                                        </Button>
                                                        <span className="min-w-8 text-center font-medium">
                                                            {qty}
                                                        </span>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            className="size-9"
                                                            onClick={() =>
                                                                adjustQuantity(
                                                                    product.id,
                                                                    1,
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
                            )}

                            {step === 3 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Schedule & review</CardTitle>
                                        <CardDescription>
                                            Pick a delivery date and review the
                                            order summary.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="scheduled_date">
                                                Delivery date
                                            </Label>
                                            <Input
                                                id="scheduled_date"
                                                type="date"
                                                required
                                                className="min-h-11"
                                                value={scheduledDate}
                                                onChange={(event) =>
                                                    setScheduledDate(
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            {errors.scheduled_date && (
                                                <p className="text-sm text-destructive">
                                                    {errors.scheduled_date}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="notes">
                                                Notes (optional)
                                            </Label>
                                            <Input
                                                id="notes"
                                                className="min-h-11"
                                                value={notes}
                                                onChange={(event) =>
                                                    setNotes(event.target.value)
                                                }
                                            />
                                        </div>

                                        <Separator />

                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">
                                                Order summary
                                            </p>
                                            {selectedItems.map((item) => (
                                                <div
                                                    key={item.product.id}
                                                    className="flex justify-between text-sm"
                                                >
                                                    <span>
                                                        {item.quantity}×{' '}
                                                        {item.product.name}
                                                    </span>
                                                    <span>
                                                        {formatMoney(
                                                            String(
                                                                Number(
                                                                    item.product
                                                                        .unit_price,
                                                                ) *
                                                                    item.quantity,
                                                            ),
                                                        )}
                                                    </span>
                                                </div>
                                            ))}
                                            <div className="flex justify-between border-t pt-2 font-semibold">
                                                <span>Estimated total</span>
                                                <span>
                                                    {formatMoney(
                                                        String(estimatedTotal),
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            <div className="flex flex-col gap-2 sm:flex-row sm:justify-between">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={index()}>Cancel</Link>
                                </Button>

                                <div className="flex gap-2">
                                    {step > 1 && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="min-h-11"
                                            onClick={() =>
                                                setStep((s) => s - 1)
                                            }
                                        >
                                            Back
                                        </Button>
                                    )}
                                    {step < 3 ? (
                                        <Button
                                            type="button"
                                            className="min-h-11"
                                            disabled={
                                                (step === 1 &&
                                                    !canProceedStep1) ||
                                                (step === 2 && !canProceedStep2)
                                            }
                                            onClick={() =>
                                                setStep((s) => s + 1)
                                            }
                                        >
                                            Continue
                                        </Button>
                                    ) : (
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing || !canSubmit
                                            }
                                            className="min-h-11 min-w-36"
                                        >
                                            {processing && <Spinner />}
                                            {processing
                                                ? 'Creating…'
                                                : 'Create draft'}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateOrder.layout = {
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: 'Create', href: create() },
    ],
};
