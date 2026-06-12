import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes/portal';
import { edit } from '@/routes/portal/profile';
import { index as depositsIndex } from '@/routes/portal/deposits';
import { index as walletIndex } from '@/routes/portal/wallet';

export default function PortalDashboard() {
    return (
        <>
            <Head title="Customer Portal" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Customer portal"
                    description="Manage your profile, wallet, deposits, orders, and subscription."
                />

                <Card>
                    <CardContent className="flex flex-col gap-4 py-8">
                        <p className="text-sm text-muted-foreground">
                            Welcome to your Jalwala customer portal. View your
                            wallet balance, jar deposits, and manage your
                            profile.
                        </p>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button asChild className="min-h-11 w-full sm:w-auto">
                                <Link href={walletIndex()}>View wallet</Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-11 w-full sm:w-auto"
                            >
                                <Link href={depositsIndex()}>
                                    View deposits
                                </Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-11 w-full sm:w-auto"
                            >
                                <Link href={edit()}>View profile</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PortalDashboard.layout = {
    breadcrumbs: [{ title: 'Portal', href: dashboard() }],
};
