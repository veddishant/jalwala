import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, IndianRupee, SlidersHorizontal, Wallet } from 'lucide-react';
import { useState } from 'react';
import CustomerWalletController from '@/actions/App/Http/Controllers/Admin/CustomerWalletController';
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
import { edit, index, wallet as walletRoute } from '@/routes/admin/customers';

type WalletInfo = {
    id: number;
    balance: string;
    low_balance_threshold: string | null;
    is_below_threshold: boolean;
    is_negative: boolean;
};

type WalletTransaction = {
    id: number;
    type: string;
    category: string;
    amount: string;
    balance_after: string;
    description: string | null;
    created_by: string | null;
    created_at: string;
};

type PaginatedTransactions = {
    data: WalletTransaction[];
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

const categoryLabel = (category: string) =>
    category.replaceAll('_', ' ');

function TopUpWalletDialog({
    customerId,
    open,
    onOpenChange,
}: {
    customerId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                onOpenChange(nextOpen);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Top up wallet</DialogTitle>
                    <DialogDescription>
                        Record a cash or UPI payment received from this
                        customer.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...CustomerWalletController.topUp.form(customerId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="top_up_amount">
                                    Amount (INR)
                                </Label>
                                <Input
                                    id="top_up_amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    disabled={processing}
                                    className="min-h-11"
                                    autoFocus
                                />
                                {errors.amount && (
                                    <p className="text-sm text-destructive">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="top_up_description">
                                    Note (optional)
                                </Label>
                                <Input
                                    id="top_up_description"
                                    name="description"
                                    disabled={processing}
                                    className="min-h-11"
                                    placeholder="e.g. Cash, UPI ref #1234"
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
                                    className="min-h-10 min-w-28"
                                >
                                    {processing && <Spinner />}
                                    {processing ? 'Adding…' : 'Add funds'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function AdjustWalletDialog({
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
                    <DialogTitle>Adjust balance</DialogTitle>
                    <DialogDescription>
                        Post a manual credit or debit correction. A reason is
                        required for the ledger.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...CustomerWalletController.adjust.form(customerId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="direction">Direction</Label>
                                <select
                                    id="direction"
                                    name="direction"
                                    required
                                    disabled={processing}
                                    className={selectClassName}
                                    defaultValue="credit"
                                >
                                    <option value="credit">
                                        Credit — add funds
                                    </option>
                                    <option value="debit">
                                        Debit — remove funds
                                    </option>
                                </select>
                                {errors.direction && (
                                    <p className="text-sm text-destructive">
                                        {errors.direction}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="adjust_amount">
                                    Amount (INR)
                                </Label>
                                <Input
                                    id="adjust_amount"
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
                                <Label htmlFor="reason">Reason</Label>
                                <Input
                                    id="reason"
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

export default function CustomerWallet({
    customer,
    wallet,
    transactions,
    can,
}: {
    customer: { id: number; name: string; code: string };
    wallet: WalletInfo;
    transactions: PaginatedTransactions;
    can: { topUp: boolean; adjust: boolean; viewLedger: boolean };
}) {
    const [topUpOpen, setTopUpOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);

    return (
        <>
            <Head title={`Wallet · ${customer.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`${customer.name} wallet`}
                        description={`Customer ${customer.code}`}
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
                                    <Wallet className="size-4" />
                                    Current balance
                                </p>
                                <p className="text-4xl font-semibold tracking-tight">
                                    {formatMoney(wallet.balance)}
                                </p>
                                <div className="flex flex-wrap gap-2 pt-1">
                                    {wallet.is_negative && (
                                        <Badge variant="destructive">
                                            Negative balance
                                        </Badge>
                                    )}
                                    {wallet.is_below_threshold && (
                                        <Badge
                                            variant="outline"
                                            className="gap-1"
                                        >
                                            <AlertTriangle className="size-3" />
                                            Below threshold
                                        </Badge>
                                    )}
                                    {wallet.low_balance_threshold && (
                                        <span className="text-sm text-muted-foreground">
                                            Alert below{' '}
                                            {formatMoney(
                                                wallet.low_balance_threshold,
                                            )}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {(can.topUp || can.adjust) && (
                        <>
                            <Separator />
                            <CardContent className="px-6 py-5">
                                <div className="space-y-3">
                                    <div>
                                        <h2 className="text-sm font-medium">
                                            Quick actions
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Record payments or post manual
                                            corrections.
                                        </p>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {can.topUp && (
                                            <Button
                                                type="button"
                                                className="min-h-11 w-full justify-center"
                                                onClick={() =>
                                                    setTopUpOpen(true)
                                                }
                                            >
                                                <IndianRupee className="size-4" />
                                                Top up wallet
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

                {can.topUp && (
                    <TopUpWalletDialog
                        customerId={customer.id}
                        open={topUpOpen}
                        onOpenChange={setTopUpOpen}
                    />
                )}

                {can.adjust && (
                    <AdjustWalletDialog
                        customerId={customer.id}
                        open={adjustOpen}
                        onOpenChange={setAdjustOpen}
                    />
                )}

                {can.adjust && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Low balance alert
                            </CardTitle>
                            <CardDescription>
                                Notify the customer when their balance drops
                                below this amount.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...CustomerWalletController.updateThreshold.form(
                                    customer.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                {({ processing }) => (
                                    <>
                                        <div className="grid flex-1 gap-2">
                                            <Label htmlFor="low_balance_threshold">
                                                Threshold (INR)
                                            </Label>
                                            <Input
                                                id="low_balance_threshold"
                                                name="low_balance_threshold"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                disabled={processing}
                                                defaultValue={
                                                    wallet.low_balance_threshold ??
                                                    ''
                                                }
                                                className="min-h-11"
                                                placeholder="e.g. 100"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="min-h-11 w-full sm:w-auto"
                                        >
                                            {processing && <Spinner />}
                                            {processing
                                                ? 'Saving…'
                                                : 'Save threshold'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {can.viewLedger && (
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-medium">
                                Transaction ledger
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Append-only history of credits and debits.
                            </p>
                        </div>

                        {transactions.data.length === 0 ? (
                            <Card>
                                <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                    No transactions yet. Top up the wallet to
                                    get started.
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
                                                        {categoryLabel(
                                                            transaction.category,
                                                        )}
                                                    </p>
                                                    <Badge
                                                        variant="outline"
                                                        className="capitalize"
                                                    >
                                                        {transaction.type}
                                                    </Badge>
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
                                                        'credit'
                                                            ? 'text-lg font-semibold text-green-600 dark:text-green-400'
                                                            : 'text-lg font-semibold text-red-600 dark:text-red-400'
                                                    }
                                                >
                                                    {transaction.type ===
                                                    'credit'
                                                        ? '+'
                                                        : '-'}
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

CustomerWallet.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Wallet', href: walletRoute(1) },
    ],
};
