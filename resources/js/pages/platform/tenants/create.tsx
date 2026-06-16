import { Form, Head, Link } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as platformDashboard } from '@/routes/platform';
import { create, index } from '@/routes/platform/tenants';

type Defaults = {
    timezone: string;
    currency: string;
};

export default function TenantCreate({ defaults }: { defaults: Defaults }) {
    return (
        <>
            <Head title="Add tenant" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Onboard supplier"
                    description="Create a new tenant and supplier admin account."
                />

                <Form
                    {...TenantController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-4 rounded-xl border p-4">
                                <h2 className="font-medium">Business</h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="business_name">
                                        Business name
                                    </Label>
                                    <Input
                                        id="business_name"
                                        name="business_name"
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.business_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="slug">
                                        URL slug (optional)
                                    </Label>
                                    <Input
                                        id="slug"
                                        name="slug"
                                        placeholder="auto-generated if blank"
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
                                            defaultValue={defaults.timezone}
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.timezone} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="currency">Currency</Label>
                                        <Input
                                            id="currency"
                                            name="currency"
                                            defaultValue={defaults.currency}
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.currency} />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-4 rounded-xl border p-4">
                                <h2 className="font-medium">Supplier admin</h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="admin_name">Full name</Label>
                                    <Input
                                        id="admin_name"
                                        name="admin_name"
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.admin_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="admin_email">Email</Label>
                                    <Input
                                        id="admin_email"
                                        name="admin_email"
                                        type="email"
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.admin_email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="admin_phone">Phone</Label>
                                    <Input
                                        id="admin_phone"
                                        name="admin_phone"
                                        type="tel"
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.admin_phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="admin_password">
                                        Password
                                    </Label>
                                    <PasswordInput
                                        id="admin_password"
                                        name="admin_password"
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors.admin_password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="admin_password_confirmation">
                                        Confirm password
                                    </Label>
                                    <PasswordInput
                                        id="admin_password_confirmation"
                                        name="admin_password_confirmation"
                                        required
                                        className="min-h-11"
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-h-11"
                                >
                                    Create tenant
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TenantCreate.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Tenants', href: index() },
        { title: 'Create', href: create() },
    ],
};
