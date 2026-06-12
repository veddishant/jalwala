import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes/portal';
import { edit } from '@/routes/portal/profile';

export default function PortalDashboard() {
    return (
        <>
            <Head title="Customer Portal" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Customer portal"
                    description="Manage your profile, orders, wallet, and subscription."
                />

                <Card>
                    <CardContent className="flex flex-col gap-4 py-8">
                        <p className="text-sm text-muted-foreground">
                            Welcome to your Jalwala customer portal. Orders,
                            wallet, and subscriptions arrive in upcoming phases.
                        </p>
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={edit()}>View profile</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PortalDashboard.layout = {
    breadcrumbs: [{ title: 'Portal', href: dashboard() }],
};
