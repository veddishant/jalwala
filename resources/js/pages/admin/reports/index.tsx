import { Head, Link } from '@inertiajs/react';
import {
    BarChart3,
    IndianRupee,
    Package,
    TrendingUp,
    Truck,
    Wallet,
} from 'lucide-react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show } from '@/routes/admin/reports';

type ReportCard = {
    type: string;
    label: string;
    description: string;
};

const iconForType = (type: string) => {
    switch (type) {
        case 'sales':
            return TrendingUp;
        case 'consumption':
            return Package;
        case 'wallet':
            return Wallet;
        case 'deposits':
            return IndianRupee;
        case 'outstanding':
            return BarChart3;
        case 'agent-performance':
            return Truck;
        default:
            return BarChart3;
    }
};

export default function ReportsIndex({ reports }: { reports: ReportCard[] }) {
    return (
        <>
            <Head title="Reports" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Reports"
                    description="Read-only analytics across sales, wallet, deposits, and deliveries."
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    {reports.map((report) => {
                        const Icon = iconForType(report.type);

                        return (
                            <Link
                                key={report.type}
                                href={show(report.type)}
                                className="block"
                            >
                                <Card className="h-full transition-colors hover:border-primary/40 hover:bg-primary/5">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Icon className="size-5 text-primary" />
                                            {report.label}
                                        </CardTitle>
                                        <CardDescription>
                                            {report.description}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <span className="text-sm font-medium text-primary">
                                            View report →
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}
