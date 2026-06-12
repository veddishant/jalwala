import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes/agent';

export default function AgentDashboard() {
    return (
        <>
            <Head title="Delivery Agent" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Delivery agent"
                    description="Assigned deliveries and route updates will appear here in a later phase."
                />

                <Card>
                    <CardContent className="py-8 text-sm text-muted-foreground">
                        No deliveries assigned yet.
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AgentDashboard.layout = {
    breadcrumbs: [{ title: 'Deliveries', href: dashboard() }],
};
