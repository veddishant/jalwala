import { Form, Head, Link } from '@inertiajs/react';
import { Minus, Plus, Wallet } from 'lucide-react';
import { useMemo, useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Portal/OrderController';
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
import { create, index } from '@/routes/portal/orders';

type ProductOption = {
    id: number;
    name: string;
    sku: string;
    unit_price: string;
    capacity_liters: string | null;
};

type AddressOption = {
    id: number;
    label: string;
    address_line_1: string;
    city: string;
    is_default: boolean;
};

const selectClassName =
    'border-input bg-background flex min-h-11 w-full rounded-md border px-3 text-sm';

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

export default function PortalCreateOrder({
    products,
    addresses,
    wallet,
}: {
    products: ProductOption[];
    addresses: AddressOption[];
    wallet: { balance: string };
}) {
    const [step, setStep] = useState(1);
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const [addressId, setAddressId] = useState(
        addresses.find((a) => a.is_default)?.id?.toString() ??
            addresses[0]?.id?.toString() ??
            '',
    );
    const [scheduledDate, setScheduledDate] = useState('');
    const [notes, setNotes] = useState('');

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

    const adjustQuantity = (productId: number, delta: number) => {
        setQuantities((current) => ({
            ...current,
            [productId]: Math.max(0, (current[productId] ?? 0) + delta),
        }));
    };

    return (
        <>
            <Head title="Place order" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Place order"
                    description="Choose products and a delivery date. Your wallet will be charged on confirmation."
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex items-center gap-2 py-4 text-sm">
                        <Wallet className="size-4" />
                        Wallet balance: {formatMoney(wallet.balance)}
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    {['Products', 'Schedule', 'Confirm'].map((label, index) => (
                        <div
                            key={label}
                            className={`flex-1 rounded-lg border px-3 py-2 text-center text-sm ${
                                step === index + 1
                                    ? 'border-primary bg-primary/5 font-medium'
                                    : 'text-muted-foreground'
                            }`}
                        >
                            {label}
                        </div>
                    ))}
                </div>

                <Form
                    {...OrderController.store.form()}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="customer_address_id"
                                value={addressId}
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
                                        <CardTitle>Choose products</CardTitle>
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

                            {step === 2 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Delivery details</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-2">
                                            <Label>Address</Label>
                                            <select
                                                className={selectClassName}
                                                value={addressId}
                                                onChange={(event) =>
                                                    setAddressId(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                {addresses.map((address) => (
                                                    <option
                                                        key={address.id}
                                                        value={address.id}
                                                    >
                                                        {address.label} —{' '}
                                                        {address.address_line_1}
                                                        , {address.city}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="scheduled_date">
                                                Delivery date
                                            </Label>
                                            <Input
                                                id="scheduled_date"
                                                type="date"
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
                                            <Label htmlFor="notes">Notes</Label>
                                            <Input
                                                id="notes"
                                                className="min-h-11"
                                                value={notes}
                                                onChange={(event) =>
                                                    setNotes(event.target.value)
                                                }
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {step === 3 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Review & place</CardTitle>
                                        <CardDescription>
                                            Your wallet will be debited
                                            immediately.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
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
                                                            ) * item.quantity,
                                                        ),
                                                    )}
                                                </span>
                                            </div>
                                        ))}
                                        <Separator />
                                        <div className="flex justify-between font-semibold">
                                            <span>Total</span>
                                            <span>
                                                {formatMoney(
                                                    String(estimatedTotal),
                                                )}
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            <div className="flex gap-2">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                                {step > 1 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => setStep((s) => s - 1)}
                                    >
                                        Back
                                    </Button>
                                )}
                                {step < 3 ? (
                                    <Button
                                        type="button"
                                        className="min-h-11 flex-1"
                                        disabled={
                                            (step === 1 &&
                                                selectedItems.length === 0) ||
                                            (step === 2 && !scheduledDate)
                                        }
                                        onClick={() => setStep((s) => s + 1)}
                                    >
                                        Continue
                                    </Button>
                                ) : (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="min-h-11 flex-1"
                                    >
                                        {processing && <Spinner />}
                                        {processing
                                            ? 'Placing…'
                                            : 'Place order'}
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PortalCreateOrder.layout = {
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: 'Place order', href: create() },
    ],
};
