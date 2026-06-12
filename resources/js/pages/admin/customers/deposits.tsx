import { Form, Head, Link } from '@inertiajs/react';
import {
    IndianRupee,
    Package,
    RotateCcw,
    SlidersHorizontal,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import CustomerDepositController from '@/actions/App/Http/Controllers/Admin/CustomerDepositController';
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
import {
    deposits as depositsRoute,
    edit,
    index,
} from '@/routes/admin/customers';

type DepositInfo = {
    id: number;
    balance: string;
    held_jar_count: number;
    closure_refund_amount: string;
};

type ReturnableProduct = {
    id: number;
    name: string;
    sku: string;
    deposit_amount: string;
    capacity_liters: string | null;
};

type JarSummaryRow = {
    product_id: number;
    product_name: string;
    jar_count: number;
    deposit_per_unit: string;
};

type DepositTransaction = {
    id: number;
    type: string;
    amount: string;
    balance_after: string;
    jar_count: number;
    product_name: string | null;
    description: string | null;
    created_by: string | null;
    created_at: string;
};

type PaginatedTransactions = {
    data: DepositTransaction[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

const typeLabel = (type: string) => type.replaceAll('_', ' ');

function CollectDepositDialog({
    customerId,
    products,
    open,
    onOpenChange,
}: {
    customerId: number;
    products: ReturnableProduct[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selectedProductId, setSelectedProductId] = useState(
        products[0]?.id?.toString() ?? '',
    );

    const selectedProduct = useMemo(
        () => products.find((p) => p.id.toString() === selectedProductId),
        [products, selectedProductId],
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Collect deposit</DialogTitle>
                    <DialogDescription>
                        Record a jar/container deposit collected from this
                        customer.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...CustomerDepositController.collect.form(customerId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="product_id">Product</Label>
                                <select
                                    id="product_id"
                                    name="product_id"
                                    required
                                    disabled={processing}
                                    className={selectClassName}
                                    value={selectedProductId}
                                    onChange={(event) =>
                                        setSelectedProductId(event.target.value)
                                    }
                                >
                                    {products.map((product) => (
                                        <option
                                            key={product.id}
                                            value={product.id}
                                        >
                                            {product.name} ({product.sku}) —{' '}
                                            {formatMoney(
                                                product.deposit_amount,
                                            )}
                                            /jar
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
                                <Label htmlFor="jar_count">Jar count</Label>
                                <Input
                                    id="jar_count"
                                    name="jar_count"
                                    type="number"
                                    min="1"
                                    required
                                    disabled={processing}
                                    className="min-h-11"
                                    defaultValue="1"
                                />
                                {selectedProduct && (
                                    <p className="text-xs text-muted-foreground">
                                        Standard deposit:{' '}
                                        {formatMoney(
                                            selectedProduct.deposit_amount,
                                        )}{' '}
                                        per jar
                                    </p>
                                )}
                                {errors.jar_count && (
                                    <p className="text-sm text-destructive">
                                        {errors.jar_count}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="collect_amount">
                                    Amount override (optional)
                                </Label>
                                <Input
                                    id="collect_amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    disabled={processing}
                                    className="min-h-11"
                                    placeholder="Leave blank to use product rate"
                                />
                                {errors.amount && (
                                    <p className="text-sm text-destructive">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="collect_description">
                                    Note (optional)
                                </Label>
                                <Input
                                    id="collect_description"
                                    name="description"
                                    disabled={processing}
                                    className="min-h-11"
                                    placeholder="e.g. Signup deposit"
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                        className="min-h-10"
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-h-10 min-w-32"
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Collecting…'
                                        : 'Collect deposit'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RefundDepositDialog({
    customerId,
    maxBalance,
    open,
    onOpenChange,
}: {
    customerId: number;
    maxBalance: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Refund deposit</DialogTitle>
                    <DialogDescription>
                        Return part of the held deposit when jars are returned.
                        Available balance: {formatMoney(maxBalance)}.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...CustomerDepositController.refund.form(customerId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="refund_jar_count">
                                    Jars returned
                                </Label>
                                <Input
                                    id="refund_jar_count"
                                    name="jar_count"
                                    type="number"
                                    min="1"
                                    required
                                    disabled={processing}
                                    className="min-h-11"
                                    defaultValue="1"
                                />
                                {errors.jar_count && (
                                    <p className="text-sm text-destructive">
                                        {errors.jar_count}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="refund_amount">
                                    Refund amount (optional)
                                </Label>
                                <Input
                                    id="refund_amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max={maxBalance}
                                    disabled={processing}
                                    className="min-h-11"
                                    placeholder="Leave blank for full balance"
                                />
                                {errors.amount && (
                                    <p className="text-sm text-destructive">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="refund_description">
                                    Note (optional)
                                </Label>
                                <Input
                                    id="refund_description"
                                    name="description"
                                    disabled={processing}
                                    className="min-h-11"
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                        className="min-h-10"
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-h-10 min-w-32"
                                >
                                    {processing && <Spinner />}
                                    {processing ? 'Refunding…' : 'Refund'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function AdjustDepositDialog({
    customerId,
    open,
    onOpenChange,
}: {
    customerId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Adjust deposit</DialogTitle>
                    <DialogDescription>
                        Post a manual correction to the deposit balance. A reason
                        is required for the ledger.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...CustomerDepositController.adjust.form(customerId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="deposit_direction">
                                    Direction
                                </Label>
                                <select
                                    id="deposit_direction"
                                    name="direction"
                                    required
                                    disabled={processing}
                                    className={selectClassName}
                                    defaultValue="increase"
                                >
                                    <option value="increase">
                                        Increase — add to held deposit
                                    </option>
                                    <option value="decrease">
                                        Decrease — reduce held deposit
                                    </option>
                                </select>
                                {errors.direction && (
                                    <p className="text-sm text-destructive">
                                        {errors.direction}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deposit_adjust_amount">
                                    Amount (INR)
                                </Label>
                                <Input
                                    id="deposit_adjust_amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    disabled={processing}
                                    className="min-h-11"
                                />
                                {errors.amount && (
                                    <p className="text-sm text-destructive">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deposit_reason">Reason</Label>
                                <Input
                                    id="deposit_reason"
                                    name="reason"
                                    required
                                    disabled={processing}
                                    className="min-h-11"
                                    placeholder="Why is this adjustment needed?"
                                />
                                {errors.reason && (
                                    <p className="text-sm text-destructive">
                                        {errors.reason}
                                    </p>
                                )}
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                        className="min-h-10"
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-h-10 min-w-36"
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Applying…'
                                        : 'Apply adjustment'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function CustomerDeposits({
    customer,
    deposit,
    jarSummary,
    products,
    transactions,
    can,
}: {
    customer: { id: number; name: string; code: string };
    deposit: DepositInfo;
    jarSummary: JarSummaryRow[];
    products: ReturnableProduct[];
    transactions: PaginatedTransactions;
    can: {
        collect: boolean;
        refund: boolean;
        adjust: boolean;
        viewLedger: boolean;
    };
}) {
    const [collectOpen, setCollectOpen] = useState(false);
    const [refundOpen, setRefundOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);

    const hasBalance = Number(deposit.balance) > 0;

    return (
        <>
            <Head title={`Deposits · ${customer.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`${customer.name} deposits`}
                        description={`Customer ${customer.code} — jar/container liability`}
                    />
                    <Button
                        asChild
                        variant="outline"
                        className="min-h-10 shrink-0"
                    >
                        <Link href={edit(customer.id)}>Back to customer</Link>
                    </Button>
                </div>

                <Card className="overflow-hidden border-primary/20">
                    <div className="bg-primary/5 px-6 py-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div className="space-y-2">
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <IndianRupee className="size-4" />
                                    Held deposit balance
                                </p>
                                <p className="text-4xl font-semibold tracking-tight">
                                    {formatMoney(deposit.balance)}
                                </p>
                                <div className="flex flex-wrap items-center gap-2 pt-1">
                                    <Badge variant="outline" className="gap-1">
                                        <Package className="size-3" />
                                        {deposit.held_jar_count} jar
                                        {deposit.held_jar_count === 1
                                            ? ''
                                            : 's'}{' '}
                                        held
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    {(can.collect || can.refund || can.adjust) && (
                        <>
                            <Separator />
                            <CardContent className="px-6 py-5">
                                <div className="space-y-3">
                                    <div>
                                        <h2 className="text-sm font-medium">
                                            Quick actions
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Collect new deposits, process
                                            returns, or post corrections.
                                        </p>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        {can.collect && products.length > 0 && (
                                            <Button
                                                type="button"
                                                className="min-h-11 w-full justify-center"
                                                onClick={() =>
                                                    setCollectOpen(true)
                                                }
                                            >
                                                <IndianRupee className="size-4" />
                                                Collect deposit
                                            </Button>
                                        )}
                                        {can.refund && hasBalance && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="min-h-11 w-full justify-center"
                                                onClick={() =>
                                                    setRefundOpen(true)
                                                }
                                            >
                                                <RotateCcw className="size-4" />
                                                Refund deposit
                                            </Button>
                                        )}
                                        {can.adjust && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="min-h-11 w-full justify-center"
                                                onClick={() =>
                                                    setAdjustOpen(true)
                                                }
                                            >
                                                <SlidersHorizontal className="size-4" />
                                                Adjust balance
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </>
                    )}
                </Card>

                {can.collect && products.length > 0 && (
                    <CollectDepositDialog
                        customerId={customer.id}
                        products={products}
                        open={collectOpen}
                        onOpenChange={setCollectOpen}
                    />
                )}

                {can.refund && hasBalance && (
                    <RefundDepositDialog
                        customerId={customer.id}
                        maxBalance={deposit.balance}
                        open={refundOpen}
                        onOpenChange={setRefundOpen}
                    />
                )}

                {can.adjust && (
                    <AdjustDepositDialog
                        customerId={customer.id}
                        open={adjustOpen}
                        onOpenChange={setAdjustOpen}
                    />
                )}

                {jarSummary.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Jars on hand
                            </CardTitle>
                            <CardDescription>
                                Returnable containers currently assigned to
                                this customer.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            {jarSummary.map((row) => (
                                <div
                                    key={row.product_id}
                                    className="flex items-center justify-between rounded-lg border px-4 py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {row.product_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatMoney(row.deposit_per_unit)}{' '}
                                            per jar
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {row.jar_count} jar
                                        {row.jar_count === 1 ? '' : 's'}
                                    </Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {hasBalance && (
                    <Card className="border-dashed">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Closure refund preview
                            </CardTitle>
                            <CardDescription>
                                Full refund amount if this customer account is
                                closed today.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold">
                                {formatMoney(deposit.closure_refund_amount)}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {can.viewLedger && (
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-medium">
                                Deposit ledger
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Append-only history of collections, refunds, and
                                adjustments.
                            </p>
                        </div>

                        {transactions.data.length === 0 ? (
                            <Card>
                                <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                    No deposit activity yet. Collect a deposit
                                    to get started.
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-3">
                                {transactions.data.map((transaction) => (
                                    <Card key={transaction.id}>
                                        <CardContent className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="min-w-0 space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-medium capitalize">
                                                        {typeLabel(
                                                            transaction.type,
                                                        )}
                                                    </p>
                                                    {transaction.jar_count >
                                                        0 && (
                                                        <Badge variant="outline">
                                                            {
                                                                transaction.jar_count
                                                            }{' '}
                                                            jar
                                                            {transaction.jar_count ===
                                                            1
                                                                ? ''
                                                                : 's'}
                                                        </Badge>
                                                    )}
                                                    {transaction.product_name && (
                                                        <Badge variant="secondary">
                                                            {
                                                                transaction.product_name
                                                            }
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {transaction.description ??
                                                        '—'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        transaction.created_at,
                                                    ).toLocaleString()}
                                                    {transaction.created_by &&
                                                        ` · ${transaction.created_by}`}
                                                </p>
                                            </div>
                                            <div className="text-left sm:text-right">
                                                <p
                                                    className={
                                                        transaction.type ===
                                                        'refund'
                                                            ? 'text-lg font-semibold text-red-600 dark:text-red-400'
                                                            : 'text-lg font-semibold text-green-600 dark:text-green-400'
                                                    }
                                                >
                                                    {transaction.type ===
                                                    'refund'
                                                        ? '-'
                                                        : '+'}
                                                    {formatMoney(
                                                        transaction.amount,
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Balance after{' '}
                                                    {formatMoney(
                                                        transaction.balance_after,
                                                    )}
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}

                        {transactions.last_page > 1 && (
                            <div className="flex flex-wrap justify-center gap-2 pt-2">
                                {transactions.links.map((link, linkIndex) =>
                                    link.url ? (
                                        <Button
                                            key={`${link.label}-${linkIndex}`}
                                            asChild
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            className="min-h-9"
                                        >
                                            <Link
                                                href={link.url}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ) : (
                                        <Button
                                            key={`${link.label}-${linkIndex}`}
                                            variant="outline"
                                            size="sm"
                                            disabled
                                            className="min-h-9"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </section>
                )}
            </div>
        </>
    );
}

CustomerDeposits.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Deposits', href: depositsRoute(1) },
    ],
};
