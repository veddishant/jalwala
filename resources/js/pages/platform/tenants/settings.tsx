import { Form, Head, Link } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as platformDashboard } from '@/routes/platform';
import { index, show } from '@/routes/platform/tenants';
import { edit as editTenantSettings } from '@/routes/platform/tenants/settings';

type TenantInfo = {
    id: number;
    name: string;
    slug: string;
};

type Settings = {
    branding: {
        logo_url: string | null;
        primary_color: string | null;
        support_email: string | null;
        support_phone: string | null;
    };
    notifications: {
        from_name: string | null;
    };
    domain: {
        custom_domain: string | null;
    };
};

export default function TenantSettings({
    tenant,
    settings,
    customDomainsEnabled,
}: {
    tenant: TenantInfo;
    settings: Settings;
    customDomainsEnabled: boolean;
}) {
    return (
        <>
            <Head title={`Settings · ${tenant.name}`} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Tenant settings"
                    description="Branding and support contact details shown to customers."
                />

                <Form
                    {...TenantController.updateSettings.form(tenant.slug)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-4 rounded-xl border p-4">
                                <h2 className="font-medium">Branding</h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="branding_logo_url">
                                        Logo URL
                                    </Label>
                                    <Input
                                        id="branding_logo_url"
                                        name="branding[logo_url]"
                                        type="url"
                                        defaultValue={
                                            settings.branding.logo_url ?? ''
                                        }
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={errors['branding.logo_url']}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="branding_primary_color">
                                        Primary color
                                    </Label>
                                    <Input
                                        id="branding_primary_color"
                                        name="branding[primary_color]"
                                        defaultValue={
                                            settings.branding.primary_color ??
                                            '#0ea5e9'
                                        }
                                        placeholder="#0ea5e9"
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            errors['branding.primary_color']
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="branding_support_email">
                                        Support email
                                    </Label>
                                    <Input
                                        id="branding_support_email"
                                        name="branding[support_email]"
                                        type="email"
                                        defaultValue={
                                            settings.branding.support_email ?? ''
                                        }
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            errors['branding.support_email']
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="branding_support_phone">
                                        Support phone
                                    </Label>
                                    <Input
                                        id="branding_support_phone"
                                        name="branding[support_phone]"
                                        type="tel"
                                        defaultValue={
                                            settings.branding.support_phone ?? ''
                                        }
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            errors['branding.support_phone']
                                        }
                                    />
                                </div>
                            </div>

                            <div className="space-y-4 rounded-xl border p-4">
                                <h2 className="font-medium">Notifications</h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="notifications_from_name">
                                        From name
                                    </Label>
                                    <Input
                                        id="notifications_from_name"
                                        name="notifications[from_name]"
                                        defaultValue={
                                            settings.notifications.from_name ??
                                            ''
                                        }
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            errors['notifications.from_name']
                                        }
                                    />
                                </div>
                            </div>

                            <div className="space-y-4 rounded-xl border p-4">
                                <h2 className="font-medium">Custom domain</h2>
                                <p className="text-sm text-muted-foreground">
                                    {customDomainsEnabled
                                        ? 'Custom domains are enabled in configuration but not yet wired to routing.'
                                        : 'Custom domains are disabled. Set TENANCY_CUSTOM_DOMAINS_ENABLED=true to prepare for a future release.'}
                                </p>

                                <div className="grid gap-2">
                                    <Label htmlFor="domain_custom_domain">
                                        Desired domain
                                    </Label>
                                    <Input
                                        id="domain_custom_domain"
                                        name="domain[custom_domain]"
                                        defaultValue={
                                            settings.domain.custom_domain ?? ''
                                        }
                                        placeholder="water.yourbrand.com"
                                        disabled={!customDomainsEnabled}
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            errors['domain.custom_domain']
                                        }
                                    />
                                </div>
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
                                    Save settings
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TenantSettings.layout = {
    breadcrumbs: [
        { title: 'Platform', href: platformDashboard() },
        { title: 'Tenants', href: index() },
        { title: 'Settings', href: editTenantSettings('placeholder') },
    ],
};
