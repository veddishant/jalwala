import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes/portal';

export default function PortalDashboard() {
    return (
        <>
            <Head title="Customer Portal" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Customer portal"
                    description="Your orders, wallet, and subscription will appear here in Phase 2."
                />

                <Card>
                    <CardContent className="py-8 text-sm text-muted-foreground">
                        Welcome to your Jalwala customer portal.
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PortalDashboard.layout = {
    breadcrumbs: [{ title: 'Portal', href: dashboard() }],
};
