import { Form, Head } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Portal/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/portal';
import { edit } from '@/routes/portal/profile';

type CustomerProfile = {
    id: number;
    code: string;
    name: string;
    phone: string;
    email: string | null;
    status: string;
    address: {
        label: string;
        address_line_1: string;
        address_line_2: string | null;
        city: string;
        state: string;
        postal_code: string;
        delivery_instructions: string | null;
    } | null;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function PortalProfile({
    customer,
}: {
    customer: CustomerProfile;
}) {
    return (
        <>
            <Head title="Profile" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Profile"
                    description={`Customer code: ${customer.code}`}
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={customer.name}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    defaultValue={customer.phone}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={customer.email ?? ''}
                                    className="min-h-11"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.label">
                                    Address label
                                </Label>
                                <Input
                                    id="address.label"
                                    name="address[label]"
                                    defaultValue={customer.address?.label ?? 'Home'}
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors['address.label']} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.address_line_1">
                                    Address line 1
                                </Label>
                                <Input
                                    id="address.address_line_1"
                                    name="address[address_line_1]"
                                    defaultValue={
                                        customer.address?.address_line_1 ?? ''
                                    }
                                    required
                                    className="min-h-11"
                                />
                                <InputError
                                    message={errors['address.address_line_1']}
                                />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="address.city">City</Label>
                                    <Input
                                        id="address.city"
                                        name="address[city]"
                                        defaultValue={customer.address?.city ?? ''}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors['address.city']} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address.state">State</Label>
                                    <Input
                                        id="address.state"
                                        name="address[state]"
                                        defaultValue={customer.address?.state ?? ''}
                                        required
                                        className="min-h-11"
                                    />
                                    <InputError message={errors['address.state']} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.postal_code">
                                    Postal code
                                </Label>
                                <Input
                                    id="address.postal_code"
                                    name="address[postal_code]"
                                    defaultValue={
                                        customer.address?.postal_code ?? ''
                                    }
                                    required
                                    className="min-h-11"
                                />
                                <InputError
                                    message={errors['address.postal_code']}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.delivery_instructions">
                                    Delivery instructions
                                </Label>
                                <textarea
                                    id="address.delivery_instructions"
                                    name="address[delivery_instructions]"
                                    defaultValue={
                                        customer.address?.delivery_instructions ??
                                        ''
                                    }
                                    rows={3}
                                    className={selectClassName}
                                />
                                <InputError
                                    message={
                                        errors['address.delivery_instructions']
                                    }
                                />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="min-h-11"
                            >
                                Save profile
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PortalProfile.layout = {
    breadcrumbs: [
        { title: 'Portal', href: dashboard() },
        { title: 'Profile', href: edit() },
    ],
};
