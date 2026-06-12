import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Wallet } from 'lucide-react';
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
import { index } from '@/routes/portal/wallet';

type WalletInfo = {
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
    created_at: string;
};

type PaginatedTransactions = {
    data: WalletTransaction[];
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

const categoryLabel = (category: string) =>
    category.replaceAll('_', ' ');

export default function PortalWallet({
    wallet,
    transactions,
}: {
    wallet: WalletInfo;
    transactions: PaginatedTransactions;
}) {
    return (
        <>
            <Head title="Wallet" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Wallet"
                    description="Your prepaid balance for water deliveries."
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardHeader>
                        <CardDescription className="flex items-center gap-2">
                            <Wallet className="size-4" />
                            Available balance
                        </CardDescription>
                        <CardTitle className="text-3xl font-semibold tracking-tight">
                            {formatMoney(wallet.balance)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {wallet.is_negative && (
                            <Badge variant="destructive">Negative balance</Badge>
                        )}
                        {wallet.is_below_threshold && (
                            <Badge variant="outline" className="gap-1">
                                <AlertTriangle className="size-3" />
                                Low balance
                            </Badge>
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <h2 className="text-lg font-medium">Transactions</h2>

                    {transactions.data.length === 0 ? (
                        <Card>
                            <CardContent className="py-8 text-center text-sm text-muted-foreground">
                                No wallet activity yet.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-3">
                            {transactions.data.map((transaction) => (
                                <Card key={transaction.id}>
                                    <CardContent className="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium capitalize">
                                                {categoryLabel(
                                                    transaction.category,
                                                )}
                                            </p>
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
                                                    'credit'
                                                        ? 'font-semibold text-green-600 dark:text-green-400'
                                                        : 'font-semibold text-red-600 dark:text-red-400'
                                                }
                                            >
                                                {transaction.type === 'credit'
                                                    ? '+'
                                                    : '-'}
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

                <Button asChild variant="outline" className="min-h-11 w-full sm:w-auto">
                    <Link href={dashboard()}>Back to portal</Link>
                </Button>
            </div>
        </>
    );
}

PortalWallet.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Wallet', href: index() },
    ],
};
