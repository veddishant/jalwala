import { Form, Head, Link } from '@inertiajs/react';
import { Eye, Inbox, MessageSquare, Search } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { dashboard as platformDashboard } from '@/routes/platform';
import { index, show } from '@/routes/platform/inquiries';

type InquiryListItem = {
    id: number;
    name: string;
    email: string;
    type: string;
    type_label: string;
    subject: string | null;
    message: string;
    status: string;
    status_label: string;
    created_at: string | null;
};

type PaginatedInquiries = {
    data: InquiryListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
};

type FilterOption = {
    value: string;
    label: string;
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'new':
            return 'default' as const;
        case 'read':
            return 'secondary' as const;
        case 'archived':
            return 'outline' as const;
        default:
            return 'outline' as const;
    }
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function InquiriesIndex({
    inquiries,
    filters,
    types,
    statuses,
    stats,
}: {
    inquiries: PaginatedInquiries;
    filters: { search: string; type: string; status: string };
    types: FilterOption[];
    statuses: FilterOption[];
    stats: { new: number; total: number };
}) {
    return (
        <>
            <Head title="Inquiries" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Inquiries"
                    description="Contact form submissions from the landing page — visible to super admins only."
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>New</CardDescription>
                            <CardTitle className="text-3xl">{stats.new}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.total}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Form action={index().url} method="get" className="flex flex-col gap-3">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search name, email, subject, or message"
                            className="min-h-11 pl-9"
                        />
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <select
                            name="type"
                            defaultValue={filters.type}
                            className={`${selectClassName} flex-1`}
                        >
                            <option value="">All types</option>
                            {types.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <select
                            name="status"
                            defaultValue={filters.status}
                            className={`${selectClassName} flex-1`}
                        >
                            <option value="">All statuses</option>
                            {statuses.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <Button
                            type="submit"
                            variant="secondary"
                            className="min-h-11"
                        >
                            Filter
                        </Button>
                    </div>
                </Form>

                {inquiries.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Inbox className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                No inquiries found.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {inquiries.data.map((inquiry) => (
                            <Card key={inquiry.id}>
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-3">
                                    <div className="min-w-0 space-y-1">
                                        <CardTitle className="truncate text-base">
                                            {inquiry.name}
                                        </CardTitle>
                                        <CardDescription className="truncate">
                                            {inquiry.email}
                                        </CardDescription>
                                    </div>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="min-h-9 shrink-0"
                                    >
                                        <Link href={show(inquiry.id)}>
                                            <Eye className="size-4" />
                                            View
                                        </Link>
                                    </Button>
                                </CardHeader>
                                <CardContent className="space-y-3 pt-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={statusVariant(inquiry.status)}>
                                            {inquiry.status_label}
                                        </Badge>
                                        <Badge variant="outline">
                                            {inquiry.type_label}
                                        </Badge>
                                    </div>
                                    {inquiry.subject && (
                                        <p className="text-sm font-medium">
                                            {inquiry.subject}
                                        </p>
                                    )}
                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                        {inquiry.message}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

InquiriesIndex.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Inquiries', href: index() },
    ],
};
