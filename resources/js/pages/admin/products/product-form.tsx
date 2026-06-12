import { Form, Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ManagedProduct = {
    id: number;
    name: string;
    sku: string;
    type: string;
    capacity_liters: string | null;
    unit_price: string;
    deposit_amount: string;
    is_returnable: boolean;
    status: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function ProductForm({
    title,
    description,
    product,
    statuses,
    types,
    submitLabel,
}: {
    title: string;
    description: string;
    product?: ManagedProduct;
    statuses: Array<{ value: string; label: string }>;
    types: Array<{ value: string; label: string }>;
    submitLabel: string;
}) {
    const isEditing = product !== undefined;

    return (
        <>
            <Head title={title} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading title={title} description={description} />

                <Form
                    {...(isEditing
                        ? ProductController.update.form(product.id)
                        : ProductController.store.form())}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={product?.name}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input
                                    id="sku"
                                    name="sku"
                                    defaultValue={product?.sku}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.sku} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>
                                    <select
                                        id="type"
                                        name="type"
                                        defaultValue={
                                            product?.type ?? types[0]?.value
                                        }
                                        required
                                        className={selectClassName}
                                    >
                                        {types.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.type} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="capacity_liters">
                                        Capacity (liters)
                                    </Label>
                                    <Input
                                        id="capacity_liters"
                                        name="capacity_liters"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={
                                            product?.capacity_liters ?? ''
                                        }
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={errors.capacity_liters}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="unit_price">
                                        Unit price (INR)
                                    </Label>
                                    <Input
                                        id="unit_price"
                                        name="unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={product?.unit_price}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.unit_price} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="deposit_amount">
                                        Deposit (INR)
                                    </Label>
                                    <Input
                                        id="deposit_amount"
                                        name="deposit_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={product?.deposit_amount}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.deposit_amount} />
                                </div>
                            </div>

                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    name="is_returnable"
                                    value="1"
                                    defaultChecked={
                                        product?.is_returnable ?? false
                                    }
                                    className="size-4 rounded border"
                                />
                                Returnable container (jar deposit applies)
                            </label>
                            <InputError message={errors.is_returnable} />

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={
                                        product?.status ?? statuses[0]?.value
                                    }
                                    required
                                    className={selectClassName}
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="min-h-11 w-full sm:w-auto"
                            >
                                {submitLabel}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
