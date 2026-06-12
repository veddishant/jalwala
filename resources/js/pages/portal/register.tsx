import { Form, Head, Link } from '@inertiajs/react';
import RegisterController from '@/actions/App/Http/Controllers/Portal/RegisterController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';

type TenantInfo = {
    name: string;
    slug: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function PortalRegister({ tenant }: { tenant: TenantInfo }) {
    return (
        <>
            <Head title={`Register · ${tenant.name}`} />

            <div className="mx-auto flex w-full max-w-md flex-col gap-6 p-4 py-10 md:p-6">
                <Heading
                    title="Create your account"
                    description={`Sign up for ${tenant.name} water delivery.`}
                />

                <Form
                    {...RegisterController.store.form(tenant.slug)}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Full name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="name"
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
                                    required
                                    autoComplete="tel"
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
                                    required
                                    autoComplete="username"
                                    className="min-h-11"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                    className="min-h-11"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    className="min-h-11"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.label">
                                    Address label
                                </Label>
                                <Input
                                    id="address.label"
                                    name="address[label]"
                                    defaultValue="Home"
                                    required
                                    className="min-h-11"
                                />
                                <InputError message={errors['address.label']} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address.address_line_1">
                                    Delivery address
                                </Label>
                                <Input
                                    id="address.address_line_1"
                                    name="address[address_line_1]"
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
                                className="min-h-11 w-full"
                            >
                                Create account
                            </Button>

                            <p className="text-center text-sm text-muted-foreground">
                                Already have an account?{' '}
                                <Link
                                    href={login()}
                                    className="text-foreground underline"
                                >
                                    Sign in
                                </Link>
                            </p>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

