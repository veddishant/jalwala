import { Form, Head, Link } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as platformDashboard } from '@/routes/platform';
import { edit, index, show } from '@/routes/platform/tenants';

type TenantForm = {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    currency: string;
    status: string;
};

type StatusOption = {
    value: string;
    label: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function TenantEdit({
    tenant,
    statuses,
}: {
    tenant: TenantForm;
    statuses: StatusOption[];
}) {
    return (
        <>
            <Head title={`Edit ${tenant.name}`} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Edit tenant"
                    description="Update core tenant profile and status."
                />

                <Form
                    {...TenantController.update.form(tenant.slug)}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Business name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={tenant.name}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">URL slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={tenant.slug}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Timezone</Label>
                                    <Input
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={tenant.timezone}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.timezone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Input
                                        id="currency"
                                        name="currency"
                                        defaultValue={tenant.currency}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.currency} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={tenant.status}
                                    className={selectClassName}
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={show(tenant.slug)}>Cancel</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-h-11"
                                >
                                    Save changes
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TenantEdit.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Tenants', href: index() },
        { title: 'Edit', href: edit('placeholder') },
    ],
};
