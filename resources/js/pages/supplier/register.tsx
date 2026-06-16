import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import SupplierRegisterController from '@/actions/App/Http/Controllers/SupplierRegisterController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';

type Option = {
    value: string;
    label: string;
};

type Defaults = {
    timezone: string;
    currency: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function SupplierRegister({
    defaults,
    timezones,
    currencies,
}: {
    defaults: Defaults;
    timezones: Option[];
    currencies: Option[];
}) {
    const [step, setStep] = useState(1);

    return (
        <>
            <Head title="Start your water delivery business" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4 py-10 md:p-6">
                <Heading
                    title="Start your supplier account"
                    description="Set up your business on Jalwala in a few steps."
                />

                <div className="flex items-center gap-2 text-sm">
                    <span
                        className={
                            step === 1
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'
                        }
                    >
                        1. Business
                    </span>
                    <span className="text-muted-foreground">→</span>
                    <span
                        className={
                            step === 2
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'
                        }
                    >
                        2. Your account
                    </span>
                </div>

                <Form
                    {...SupplierRegisterController.store.form()}
                    className="space-y-5"
                    onSuccess={() => setStep(1)}
                >
                    {({ processing, errors }) => (
                        <>
                            {step === 1 && (
                                <div className="space-y-4">
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
                                        <InputError
                                            message={errors.business_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="slug">
                                            Customer portal slug (optional)
                                        </Label>
                                        <Input
                                            id="slug"
                                            name="slug"
                                            placeholder="your-business-name"
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.slug} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="timezone">
                                                Timezone
                                            </Label>
                                            <select
                                                id="timezone"
                                                name="timezone"
                                                defaultValue={defaults.timezone}
                                                className={selectClassName}
                                            >
                                                {timezones.map((tz) => (
                                                    <option
                                                        key={tz.value}
                                                        value={tz.value}
                                                    >
                                                        {tz.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.timezone}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="currency">
                                                Currency
                                            </Label>
                                            <select
                                                id="currency"
                                                name="currency"
                                                defaultValue={defaults.currency}
                                                className={selectClassName}
                                            >
                                                {currencies.map((currency) => (
                                                    <option
                                                        key={currency.value}
                                                        value={currency.value}
                                                    >
                                                        {currency.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.currency}
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        className="min-h-11 w-full"
                                        onClick={() => setStep(2)}
                                    >
                                        Continue
                                    </Button>
                                </div>
                            )}

                            {step === 2 && (
                                <div className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Your name</Label>
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
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Password
                                        </Label>
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
                                    </div>

                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="min-h-11 flex-1"
                                            onClick={() => setStep(1)}
                                        >
                                            Back
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="min-h-11 flex-1"
                                        >
                                            Create account
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </Form>

                <p className="text-center text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <Link href={login()} className="text-primary underline">
                        Sign in
                    </Link>
                </p>
            </div>
        </>
    );
}
