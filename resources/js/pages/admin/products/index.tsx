import { Form, Head, Link, router } from '@inertiajs/react';
import { IndianRupee, Package, Pencil, Plus, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, edit, index } from '@/routes/admin/products';

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

export default function ProductsIndex({
    products,
    filters,
    statuses,
    can,
}: {
    products: ManagedProduct[];
    filters: { search: string; status: string };
    statuses: Array<{ value: string; label: string }>;
    can: { create: boolean; update: boolean; deactivate: boolean };
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            index.url({ query: { search, status: status || undefined } }),
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Products" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Products"
                        description="Manage your water delivery catalog, pricing, and jar deposits."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Add product
                            </Link>
                        </Button>
                    )}
                </div>

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 sm:flex-row"
                >
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name or SKU"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="border-input bg-background flex min-h-11 rounded-md border px-3 py-2 text-sm shadow-xs"
                    >
                        <option value="">All statuses</option>
                        {statuses.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" className="min-h-11">
                        Filter
                    </Button>
                </form>

                {products.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Package className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                No products yet. Add jars, bottles, or
                                accessories to your catalog.
                            </p>
                            {can.create && (
                                <Button asChild>
                                    <Link href={create()}>Add product</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {products.map((product) => (
                            <Card key={product.id} className="flex flex-col">
                                <CardHeader className="space-y-2 pb-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <CardTitle className="text-base">
                                            {product.name}
                                        </CardTitle>
                                        <Badge
                                            variant={
                                                product.status === 'active'
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {product.status}
                                        </Badge>
                                    </div>
                                    <CardDescription>
                                        {product.sku}
                                        {product.capacity_liters
                                            ? ` · ${product.capacity_liters}L`
                                            : ''}
                                    </CardDescription>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="outline">
                                            {product.type}
                                        </Badge>
                                        {product.is_returnable && (
                                            <Badge variant="outline">
                                                Returnable
                                            </Badge>
                                        )}
                                    </div>
                                </CardHeader>

                                {can.update && (
                                    <CardContent className="space-y-3 pt-0">
                                        <Form
                                            {...ProductController.updatePrice.form(
                                                product.id,
                                            )}
                                            options={{ preserveScroll: true }}
                                            className="space-y-3 rounded-lg border bg-muted/30 p-3"
                                        >
                                            <p className="flex items-center gap-1 text-xs font-medium text-muted-foreground">
                                                <IndianRupee className="size-3" />
                                                Quick price edit
                                            </p>
                                            <div className="grid grid-cols-2 gap-2">
                                                <div className="grid gap-1">
                                                    <Label
                                                        htmlFor={`unit_price-${product.id}`}
                                                        className="text-xs"
                                                    >
                                                        Unit price
                                                    </Label>
                                                    <Input
                                                        id={`unit_price-${product.id}`}
                                                        name="unit_price"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        defaultValue={
                                                            product.unit_price
                                                        }
                                                        required
                                                        className="min-h-9"
                                                    />
                                                </div>
                                                <div className="grid gap-1">
                                                    <Label
                                                        htmlFor={`deposit_amount-${product.id}`}
                                                        className="text-xs"
                                                    >
                                                        Deposit
                                                    </Label>
                                                    <Input
                                                        id={`deposit_amount-${product.id}`}
                                                        name="deposit_amount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        defaultValue={
                                                            product.deposit_amount
                                                        }
                                                        required
                                                        className="min-h-9"
                                                    />
                                                </div>
                                            </div>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="secondary"
                                                className="min-h-9 w-full"
                                            >
                                                Save prices
                                            </Button>
                                        </Form>
                                    </CardContent>
                                )}

                                <CardFooter className="mt-auto flex flex-wrap gap-2 pt-0">
                                    {can.update && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="min-h-9"
                                        >
                                            <Link href={edit(product.id)}>
                                                <Pencil className="size-4" />
                                                Edit
                                            </Link>
                                        </Button>
                                    )}
                                    {can.deactivate &&
                                        (product.status === 'active' ? (
                                            <Form
                                                {...ProductController.deactivate.form(
                                                    product.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    size="sm"
                                                    className="min-h-9"
                                                >
                                                    Deactivate
                                                </Button>
                                            </Form>
                                        ) : (
                                            <Form
                                                {...ProductController.activate.form(
                                                    product.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    className="min-h-9"
                                                >
                                                    Activate
                                                </Button>
                                            </Form>
                                        ))}
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Products', href: index() }],
};
