import { Form, Head, Link } from '@inertiajs/react';
import { Archive, ArrowLeft, Mail, Phone, User } from 'lucide-react';
import InquiryController from '@/actions/App/Http/Controllers/Platform/InquiryController';
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
import { dashboard as platformDashboard } from '@/routes/platform';
import { index, show } from '@/routes/platform/inquiries';

type InquiryDetail = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    type: string;
    type_label: string;
    subject: string | null;
    message: string;
    status: string;
    status_label: string;
    ip_address: string | null;
    user_agent: string | null;
    read_at: string | null;
    created_at: string | null;
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

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export default function InquiryShow({
    inquiry,
    can,
}: {
    inquiry: InquiryDetail;
    can: { update: boolean };
}) {
    const isArchived = inquiry.status === 'archived';

    return (
        <>
            <Head title={`Inquiry from ${inquiry.name}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={inquiry.name}
                        description={inquiry.type_label}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            asChild
                            variant="outline"
                            className="min-h-11"
                        >
                            <Link href={index()}>
                                <ArrowLeft className="size-4" />
                                Back
                            </Link>
                        </Button>
                        {can.update && !isArchived && (
                            <Form
                                {...InquiryController.archive.form(inquiry.id)}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={processing}
                                        className="min-h-11"
                                    >
                                        <Archive className="size-4" />
                                        Archive
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant={statusVariant(inquiry.status)}>
                        {inquiry.status_label}
                    </Badge>
                    <Badge variant="outline">{inquiry.type_label}</Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Message</CardTitle>
                            {inquiry.subject && (
                                <CardDescription className="text-base text-foreground">
                                    {inquiry.subject}
                                </CardDescription>
                            )}
                        </CardHeader>
                        <CardContent>
                            <p className="whitespace-pre-wrap text-sm leading-relaxed">
                                {inquiry.message}
                            </p>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Contact
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <p className="flex items-center gap-2">
                                    <User className="size-4 text-muted-foreground" />
                                    {inquiry.name}
                                </p>
                                <p className="flex items-center gap-2">
                                    <Mail className="size-4 text-muted-foreground" />
                                    <a
                                        href={`mailto:${inquiry.email}`}
                                        className="text-primary hover:underline"
                                    >
                                        {inquiry.email}
                                    </a>
                                </p>
                                {inquiry.phone && (
                                    <p className="flex items-center gap-2">
                                        <Phone className="size-4 text-muted-foreground" />
                                        <a
                                            href={`tel:${inquiry.phone}`}
                                            className="hover:underline"
                                        >
                                            {inquiry.phone}
                                        </a>
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Metadata
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm text-muted-foreground">
                                <p>
                                    <span className="text-foreground">
                                        Submitted:
                                    </span>{' '}
                                    {formatDate(inquiry.created_at)}
                                </p>
                                <p>
                                    <span className="text-foreground">
                                        Read:
                                    </span>{' '}
                                    {formatDate(inquiry.read_at)}
                                </p>
                                {inquiry.ip_address && (
                                    <p>
                                        <span className="text-foreground">
                                            IP:
                                        </span>{' '}
                                        {inquiry.ip_address}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

InquiryShow.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Inquiries', href: index() },
        {
            title: 'Detail',
            href: show(':id'),
        },
    ],
};
