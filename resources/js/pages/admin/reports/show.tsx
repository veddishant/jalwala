import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Download, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { index, show } from '@/routes/admin/reports';

type ReportMeta = {
    type: string;
    label: string;
    description: string;
};

type Filters = {
    date_from: string;
    date_to: string;
    grain: string;
    customer_id: number | null;
    product_id: number | null;
    source: string | null;
    agent_id: number | null;
};

type FilterOptions = {
    customers: Array<{ id: number; name: string; code: string }>;
    products: Array<{ id: number; name: string; sku: string }>;
    agents: Array<{ id: number; name: string }>;
    sources: Array<{ value: string; label: string }>;
    grains: Array<{ value: string; label: string }>;
};

type ReportData = {
    summary: Record<string, string | number>;
    rows: Array<Record<string, string | number | null>>;
    by_period?: Array<Record<string, string | number>>;
    by_product?: Array<Record<string, string | number>>;
    by_customer?: Array<Record<string, string | number>>;
    by_aging?: Array<Record<string, string | number>>;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

function buildExportUrl(type: string, filters: Filters): string {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== null && value !== '') {
            params.set(key, String(value));
        }
    });

    return `/admin/reports/${type}/export?${params.toString()}`;
}

function ReportSkeleton() {
    return (
        <div className="space-y-4 animate-pulse">
            <div className="grid gap-4 sm:grid-cols-3">
                {[1, 2, 3].map((key) => (
                    <div
                        key={key}
                        className="h-24 rounded-lg bg-muted"
                    />
                ))}
            </div>
            <div className="h-64 rounded-lg bg-muted" />
        </div>
    );
}

function SummaryCards({
    reportType,
    summary,
}: {
    reportType: string;
    summary: Record<string, string | number>;
}) {
    const entries = Object.entries(summary);

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {entries.map(([key, value]) => (
                <Card key={key}>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            {key.replaceAll('_', ' ')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold">{value}</p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function DataTable({
    rows,
}: {
    rows: Array<Record<string, string | number | null>>;
}) {
    if (rows.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No data for the selected filters.
            </p>
        );
    }

    const columns = Object.keys(rows[0]);

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr className="border-b">
                        {columns.map((column) => (
                            <th
                                key={column}
                                className="px-3 py-2 font-medium capitalize text-muted-foreground"
                            >
                                {column.replaceAll('_', ' ')}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, index) => (
                        <tr key={index} className="border-b last:border-0">
                            {columns.map((column) => (
                                <td key={column} className="px-3 py-2">
                                    {row[column] ?? '—'}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function FilterForm({
    reportType,
    filters,
    filterOptions,
    onApplied,
}: {
    reportType: string;
    filters: Filters;
    filterOptions: FilterOptions;
    onApplied?: () => void;
}) {
    const applyFilters = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const params = Object.fromEntries(formData.entries());

        router.get(show(reportType), params, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => onApplied?.(),
        });
    };

    const showDateFilters = reportType !== 'outstanding';
    const showGrain = reportType === 'sales';
    const showSource = reportType === 'sales';
    const showCustomer = reportType !== 'agent-performance';
    const showProduct =
        reportType === 'sales' ||
        reportType === 'consumption' ||
        reportType === 'deposits';
    const showAgent = reportType === 'agent-performance';

    return (
        <form onSubmit={applyFilters} className="space-y-4">
            {showDateFilters && (
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <label htmlFor="date_from" className="text-sm font-medium">
                            From
                        </label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            defaultValue={filters.date_from}
                            className={selectClassName}
                        />
                    </div>
                    <div className="grid gap-2">
                        <label htmlFor="date_to" className="text-sm font-medium">
                            To
                        </label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            defaultValue={filters.date_to}
                            className={selectClassName}
                        />
                    </div>
                </div>
            )}

            {showGrain && (
                <div className="grid gap-2">
                    <label htmlFor="grain" className="text-sm font-medium">
                        Period grain
                    </label>
                    <select
                        id="grain"
                        name="grain"
                        defaultValue={filters.grain}
                        className={selectClassName}
                    >
                        {filterOptions.grains.map((grain) => (
                            <option key={grain.value} value={grain.value}>
                                {grain.label}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {showCustomer && (
                <div className="grid gap-2">
                    <label htmlFor="customer_id" className="text-sm font-medium">
                        Customer
                    </label>
                    <select
                        id="customer_id"
                        name="customer_id"
                        defaultValue={filters.customer_id ?? ''}
                        className={selectClassName}
                    >
                        <option value="">All customers</option>
                        {filterOptions.customers.map((customer) => (
                            <option key={customer.id} value={customer.id}>
                                {customer.name} ({customer.code})
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {showProduct && (
                <div className="grid gap-2">
                    <label htmlFor="product_id" className="text-sm font-medium">
                        Product
                    </label>
                    <select
                        id="product_id"
                        name="product_id"
                        defaultValue={filters.product_id ?? ''}
                        className={selectClassName}
                    >
                        <option value="">All products</option>
                        {filterOptions.products.map((product) => (
                            <option key={product.id} value={product.id}>
                                {product.name}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {showSource && (
                <div className="grid gap-2">
                    <label htmlFor="source" className="text-sm font-medium">
                        Order source
                    </label>
                    <select
                        id="source"
                        name="source"
                        defaultValue={filters.source ?? ''}
                        className={selectClassName}
                    >
                        <option value="">All sources</option>
                        {filterOptions.sources.map((source) => (
                            <option key={source.value} value={source.value}>
                                {source.label}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {showAgent && (
                <div className="grid gap-2">
                    <label htmlFor="agent_id" className="text-sm font-medium">
                        Delivery agent
                    </label>
                    <select
                        id="agent_id"
                        name="agent_id"
                        defaultValue={filters.agent_id ?? ''}
                        className={selectClassName}
                    >
                        <option value="">All agents</option>
                        {filterOptions.agents.map((agent) => (
                            <option key={agent.id} value={agent.id}>
                                {agent.name}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            <Button type="submit" className="min-h-11 w-full">
                Apply filters
            </Button>
        </form>
    );
}

function ReportContent({
    reportType,
    report,
}: {
    reportType: string;
    report: ReportData;
}) {
    const secondaryRows =
        report.by_period ??
        report.by_product ??
        report.by_customer ??
        report.by_aging ??
        [];

    return (
        <div className="space-y-6">
            <SummaryCards
                reportType={reportType}
                summary={report.summary}
            />

            {secondaryRows.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DataTable rows={secondaryRows} />
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>Detail</CardTitle>
                </CardHeader>
                <CardContent>
                    <DataTable rows={report.rows} />
                </CardContent>
            </Card>
        </div>
    );
}

export default function ReportShow({
    reportType,
    filters,
    filterOptions,
    can,
    report,
}: {
    reportType: ReportMeta;
    filters: Filters;
    filterOptions: FilterOptions;
    can: { export: boolean };
    report?: ReportData;
}) {
    const [filtersOpen, setFiltersOpen] = useState(false);

    const exportUrl = buildExportUrl(reportType.type, filters);

    return (
        <>
            <Head title={reportType.label} />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={reportType.label}
                        description={reportType.description}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="min-h-10">
                            <Link href={index()}>All reports</Link>
                        </Button>

                        <Sheet open={filtersOpen} onOpenChange={setFiltersOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="outline"
                                    className="min-h-10 md:hidden"
                                >
                                    <SlidersHorizontal className="size-4" />
                                    Filters
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="bottom" className="max-h-[85vh] overflow-y-auto">
                                <SheetHeader>
                                    <SheetTitle>Report filters</SheetTitle>
                                </SheetHeader>
                                <div className="px-4 pb-6">
                                    <FilterForm
                                        reportType={reportType.type}
                                        filters={filters}
                                        filterOptions={filterOptions}
                                        onApplied={() => setFiltersOpen(false)}
                                    />
                                </div>
                            </SheetContent>
                        </Sheet>

                        {can.export && (
                            <Button asChild className="min-h-10">
                                <a href={exportUrl}>
                                    <Download className="size-4" />
                                    Export CSV
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <Card className="hidden md:block">
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <FilterForm
                            reportType={reportType.type}
                            filters={filters}
                            filterOptions={filterOptions}
                        />
                    </CardContent>
                </Card>

                <Deferred data="report" fallback={<ReportSkeleton />}>
                    <ReportContent
                        reportType={reportType.type}
                        report={report as ReportData}
                    />
                </Deferred>
            </div>
        </>
    );
}
