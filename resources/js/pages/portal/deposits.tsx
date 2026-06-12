import { Head, Link } from '@inertiajs/react';
import { IndianRupee, Package } from 'lucide-react';
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
import { dashboard } from '@/routes/portal';
import { index } from '@/routes/portal/deposits';

type DepositInfo = {
    balance: string;
    held_jar_count: number;
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
    created_at: string;
};

type PaginatedTransactions = {
    data: DepositTransaction[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

const formatMoney = (value: string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value));

const typeLabel = (type: string) => type.replaceAll('_', ' ');

export default function PortalDeposits({
    deposit,
    jarSummary,
    transactions,
}: {
    deposit: DepositInfo;
    jarSummary: JarSummaryRow[];
    transactions: PaginatedTransactions;
}) {
    return (
        <>
            <Head title="Deposits" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Jar deposits"
                    description="Your held container deposits — separate from your wallet balance."
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardHeader>
                        <CardDescription className="flex items-center gap-2">
                            <IndianRupee className="size-4" />
                            Held deposit balance
                        </CardDescription>
                        <CardTitle className="text-3xl font-semibold tracking-tight">
                            {formatMoney(deposit.balance)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Badge variant="outline" className="gap-1">
                            <Package className="size-3" />
                            {deposit.held_jar_count} jar
                            {deposit.held_jar_count === 1 ? '' : 's'} on hand
                        </Badge>
                    </CardContent>
                </Card>

                {jarSummary.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Containers on hand
                            </CardTitle>
                            <CardDescription>
                                Returnable jars currently assigned to you.
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
                                            deposit per jar
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {row.jar_count}
                                    </Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-4">
                    <h2 className="text-lg font-medium">Deposit history</h2>

                    {transactions.data.length === 0 ? (
                        <Card>
                            <CardContent className="py-8 text-center text-sm text-muted-foreground">
                                No deposit activity yet.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-3">
                            {transactions.data.map((transaction) => (
                                <Card key={transaction.id}>
                                    <CardContent className="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium capitalize">
                                                {typeLabel(transaction.type)}
                                            </p>
                                            {transaction.product_name && (
                                                <p className="text-sm text-muted-foreground">
                                                    {transaction.product_name}
                                                    {transaction.jar_count > 0 &&
                                                        ` · ${transaction.jar_count} jar${transaction.jar_count === 1 ? '' : 's'}`}
                                                </p>
                                            )}
                                            {transaction.description && (
                                                <p className="text-sm text-muted-foreground">
                                                    {transaction.description}
                                                </p>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(
                                                    transaction.created_at,
                                                ).toLocaleString()}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p
                                                className={
                                                    transaction.type ===
                                                    'refund'
                                                        ? 'font-semibold text-red-600 dark:text-red-400'
                                                        : 'font-semibold text-green-600 dark:text-green-400'
                                                }
                                            >
                                                {transaction.type === 'refund'
                                                    ? '-'
                                                    : '+'}
                                                {formatMoney(transaction.amount)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Balance{' '}
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
                        <div className="flex flex-wrap gap-2">
                            {transactions.links.map((link, linkIndex) =>
                                link.url ? (
                                    <Button
                                        key={`${link.label}-${linkIndex}`}
                                        asChild
                                        variant={
                                            link.active ? 'default' : 'outline'
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
                </div>

                <Button
                    asChild
                    variant="outline"
                    className="min-h-11 w-full sm:w-auto"
                >
                    <Link href={dashboard()}>Back to portal</Link>
                </Button>
            </div>
        </>
    );
}

PortalDeposits.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Deposits', href: index() },
    ],
};
