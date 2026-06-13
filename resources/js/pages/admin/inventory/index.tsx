import { Form, Head } from '@inertiajs/react';
import { Plus, SlidersHorizontal, Warehouse } from 'lucide-react';
import { useState } from 'react';
import InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
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

type BalanceRow = {
    id: number;
    product_id: number;
    product_name: string;
    sku: string;
    capacity_liters: string | null;
    filled_quantity: number;
    empty_quantity: number;
};

type ReturnableProduct = {
    id: number;
    name: string;
    sku: string;
};

type MovementRow = {
    id: number;
    movement_type: string;
    movement_label: string;
    quantity: number;
    product_name: string | null;
    notes: string | null;
    reference_type: string | null;
    created_by: string | null;
    created_at: string;
};

type PaginatedMovements = {
    data: MovementRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function InventoryIndex({
    warehouse,
    balances,
    products,
    movements,
    can,
}: {
    warehouse: { id: number; name: string };
    balances: BalanceRow[];
    products: ReturnableProduct[];
    movements: PaginatedMovements;
    can: { adjust: boolean; receiveStock: boolean };
}) {
    const [receiveOpen, setReceiveOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);

    const totalFilled = balances.reduce(
        (sum, row) => sum + row.filled_quantity,
        0,
    );
    const totalEmpty = balances.reduce(
        (sum, row) => sum + row.empty_quantity,
        0,
    );

    return (
        <>
            <Head title="Warehouse inventory" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Warehouse inventory"
                        description={`${warehouse.name} — filled and empty jar stock`}
                    />
                    <div className="flex flex-wrap gap-2">
                        {can.receiveStock && (
                            <Button
                                className="min-h-10"
                                onClick={() => setReceiveOpen(true)}
                            >
                                <Plus className="size-4" />
                                Receive stock
                            </Button>
                        )}
                        {can.adjust && (
                            <Button
                                variant="outline"
                                className="min-h-10"
                                onClick={() => setAdjustOpen(true)}
                            >
                                <SlidersHorizontal className="size-4" />
                                Adjust
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Filled jars</CardDescription>
                            <CardTitle className="text-3xl">{totalFilled}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Empty jars</CardDescription>
                            <CardTitle className="text-3xl">{totalEmpty}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Warehouse className="size-5" />
                            Stock by product
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {balances.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No stock recorded yet. Receive stock to get
                                started.
                            </p>
                        ) : (
                            balances.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-4"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {row.product_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {row.sku}
                                            {row.capacity_liters &&
                                                ` · ${row.capacity_liters}L`}
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
                        <CardTitle>Recent movements</CardTitle>
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
                                    <div className="flex flex-wrap items-center justify-between gap-2">
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

            {can.receiveStock && (
                <Dialog open={receiveOpen} onOpenChange={setReceiveOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Receive stock</DialogTitle>
                        </DialogHeader>
                        <Form
                            {...InventoryController.receiveStock.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            onSuccess={() => setReceiveOpen(false)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="receive_product">
                                            Product
                                        </Label>
                                        <select
                                            id="receive_product"
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
                                        {errors.product_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.product_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="receive_quantity">
                                            Quantity
                                        </Label>
                                        <Input
                                            id="receive_quantity"
                                            name="quantity"
                                            type="number"
                                            min={1}
                                            required
                                            className="min-h-11"
                                        />
                                        {errors.quantity && (
                                            <p className="text-sm text-destructive">
                                                {errors.quantity}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="receive_notes">
                                            Notes (optional)
                                        </Label>
                                        <Input
                                            id="receive_notes"
                                            name="notes"
                                            className="min-h-11"
                                        />
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
                                            Receive
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            )}

            {can.adjust && (
                <Dialog open={adjustOpen} onOpenChange={setAdjustOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Adjust inventory</DialogTitle>
                        </DialogHeader>
                        <Form
                            {...InventoryController.adjust.form()}
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
                                            Save adjustment
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
