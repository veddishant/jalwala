import { Form, Head, Link } from '@inertiajs/react';
import { Package, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import CustomerInventoryController from '@/actions/App/Http/Controllers/Admin/CustomerInventoryController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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
import { edit } from '@/routes/admin/customers';

type SummaryRow = {
    product_id: number;
    product_name: string;
    sku: string;
    filled_quantity: number;
    empty_quantity: number;
    total_jars: number;
};

type ReturnableProduct = {
    id: number;
    name: string;
    sku: string;
};

type MovementRow = {
    id: number;
    movement_label: string;
    quantity: number;
    product_name: string | null;
    notes: string | null;
    created_by: string | null;
    created_at: string;
};

type PaginatedMovements = {
    data: MovementRow[];
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function CustomerInventory({
    customer,
    location,
    summary,
    products,
    movements,
    can,
}: {
    customer: { id: number; name: string; code: string };
    location: { id: number; name: string };
    summary: SummaryRow[];
    products: ReturnableProduct[];
    movements: PaginatedMovements;
    can: { adjust: boolean };
}) {
    const [adjustOpen, setAdjustOpen] = useState(false);

    const totalFilled = summary.reduce(
        (sum, row) => sum + row.filled_quantity,
        0,
    );
    const totalEmpty = summary.reduce(
        (sum, row) => sum + row.empty_quantity,
        0,
    );

    return (
        <>
            <Head title={`Inventory · ${customer.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={customer.name}
                        description={`${customer.code} · ${location.name}`}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="min-h-10">
                            <Link href={edit(customer.id)}>Back to customer</Link>
                        </Button>
                        {can.adjust && (
                            <Button
                                className="min-h-10"
                                onClick={() => setAdjustOpen(true)}
                            >
                                <SlidersHorizontal className="size-4" />
                                Adjust
                            </Button>
                        )}
                    </div>
                </div>

                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex flex-wrap gap-4 py-6">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Filled at premises
                            </p>
                            <p className="text-3xl font-semibold">{totalFilled}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Empty awaiting pickup
                            </p>
                            <p className="text-3xl font-semibold">{totalEmpty}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="size-5" />
                            Jars on premises
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {summary.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No jars recorded at this customer location yet.
                            </p>
                        ) : (
                            summary.map((row) => (
                                <div
                                    key={row.product_id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-4"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {row.product_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.sku}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Badge variant="secondary">
                                            {row.filled_quantity} filled
                                        </Badge>
                                        <Badge variant="outline">
                                            {row.empty_quantity} empty
                                        </Badge>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Movement history</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {movements.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No movements yet.
                            </p>
                        ) : (
                            movements.data.map((movement) => (
                                <div
                                    key={movement.id}
                                    className="rounded-lg border p-4 text-sm"
                                >
                                    <div className="flex flex-wrap justify-between gap-2">
                                        <span className="font-medium">
                                            {movement.movement_label} ·{' '}
                                            {movement.quantity}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {movement.product_name}
                                        </span>
                                    </div>
                                    {movement.notes && (
                                        <p className="mt-1 text-muted-foreground">
                                            {movement.notes}
                                        </p>
                                    )}
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {movement.created_by ?? 'System'} ·{' '}
                                        {new Date(
                                            movement.created_at,
                                        ).toLocaleString()}
                                    </p>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>

            {can.adjust && (
                <Dialog open={adjustOpen} onOpenChange={setAdjustOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Adjust customer inventory</DialogTitle>
                        </DialogHeader>
                        <Form
                            {...CustomerInventoryController.adjust.form(
                                customer.id,
                            )}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            onSuccess={() => setAdjustOpen(false)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="adjust_product">
                                            Product
                                        </Label>
                                        <select
                                            id="adjust_product"
                                            name="product_id"
                                            className={selectClassName}
                                            required
                                        >
                                            <option value="">
                                                Select product
                                            </option>
                                            {products.map((product) => (
                                                <option
                                                    key={product.id}
                                                    value={product.id}
                                                >
                                                    {product.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="adjust_jar_type">
                                            Jar type
                                        </Label>
                                        <select
                                            id="adjust_jar_type"
                                            name="jar_type"
                                            className={selectClassName}
                                            required
                                        >
                                            <option value="filled">
                                                Filled
                                            </option>
                                            <option value="empty">Empty</option>
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="adjust_direction">
                                            Direction
                                        </Label>
                                        <select
                                            id="adjust_direction"
                                            name="direction"
                                            className={selectClassName}
                                            required
                                        >
                                            <option value="increase">
                                                Increase
                                            </option>
                                            <option value="decrease">
                                                Decrease
                                            </option>
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="adjust_quantity">
                                            Quantity
                                        </Label>
                                        <Input
                                            id="adjust_quantity"
                                            name="quantity"
                                            type="number"
                                            min={1}
                                            required
                                            className="min-h-11"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="adjust_reason">
                                            Reason
                                        </Label>
                                        <Input
                                            id="adjust_reason"
                                            name="reason"
                                            required
                                            className="min-h-11"
                                        />
                                        {errors.reason && (
                                            <p className="text-sm text-destructive">
                                                {errors.reason}
                                            </p>
                                        )}
                                    </div>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                            >
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Save
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}
