import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CustomerAddress = {
    label: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    postal_code: string;
    delivery_instructions: string | null;
};

type ManagedCustomer = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    status: string;
    notes: string | null;
    address: CustomerAddress | null;
    has_portal_account: boolean;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function CustomerForm({
    title,
    description,
    customer,
    statuses,
    can,
    submitLabel,
}: {
    title: string;
    description: string;
    customer?: ManagedCustomer;
    statuses: Array<{ value: string; label: string }>;
    can?: {
        manageAddresses?: boolean;
        createPortal?: boolean;
    };
    submitLabel: string;
}) {
    const isEditing = customer !== undefined;
    const [step, setStep] = useState(1);
    const [createPortal, setCreatePortal] = useState(false);
    const totalSteps = isEditing ? 1 : 3;

    return (
        <>
            <Head title={title} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading title={title} description={description} />

                {!isEditing && (
                    <div className="flex gap-2">
                        {[1, 2, 3].map((value) => (
                            <div
                                key={value}
                                className={`h-1 flex-1 rounded-full ${
                                    value <= step
                                        ? 'bg-primary'
                                        : 'bg-muted'
                                }`}
                            />
                        ))}
                    </div>
                )}

                <Form
                    {...(isEditing
                        ? CustomerController.update.form(customer.id)
                        : CustomerController.store.form())}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div
                                className={`space-y-6 ${!isEditing && step !== 1 ? 'hidden' : ''}`}
                            >
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={customer?.name}
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
                                            defaultValue={customer?.phone}
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
                                            defaultValue={customer?.email ?? ''}
                                            autoComplete="email"
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="status">Status</Label>
                                        <select
                                            id="status"
                                            name="status"
                                            defaultValue={
                                                customer?.status ??
                                                statuses[0]?.value
                                            }
                                            required
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

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <textarea
                                            id="notes"
                                            name="notes"
                                            defaultValue={customer?.notes ?? ''}
                                            rows={3}
                                            className={selectClassName}
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                            </div>

                            <div
                                className={`space-y-6 ${
                                    isEditing
                                        ? can?.manageAddresses
                                            ? ''
                                            : 'hidden'
                                        : step !== 2
                                          ? 'hidden'
                                          : ''
                                }`}
                            >
                                    <div className="grid gap-2">
                                        <Label htmlFor="address.label">
                                            Address label
                                        </Label>
                                        <Input
                                            id="address.label"
                                            name="address[label]"
                                            defaultValue={
                                                customer?.address?.label ??
                                                'Home'
                                            }
                                            required
                                            className="min-h-11"
                                        />
                                        <InputError
                                            message={errors['address.label']}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address.address_line_1">
                                            Address line 1
                                        </Label>
                                        <Input
                                            id="address.address_line_1"
                                            name="address[address_line_1]"
                                            defaultValue={
                                                customer?.address
                                                    ?.address_line_1 ?? ''
                                            }
                                            required
                                            className="min-h-11"
                                        />
                                        <InputError
                                            message={
                                                errors['address.address_line_1']
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address.address_line_2">
                                            Address line 2
                                        </Label>
                                        <Input
                                            id="address.address_line_2"
                                            name="address[address_line_2]"
                                            defaultValue={
                                                customer?.address
                                                    ?.address_line_2 ?? ''
                                            }
                                            className="min-h-11"
                                        />
                                        <InputError
                                            message={
                                                errors['address.address_line_2']
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="address.city">
                                                City
                                            </Label>
                                            <Input
                                                id="address.city"
                                                name="address[city]"
                                                defaultValue={
                                                    customer?.address?.city ??
                                                    ''
                                                }
                                                required
                                                className="min-h-11"
                                            />
                                            <InputError
                                                message={errors['address.city']}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="address.state">
                                                State
                                            </Label>
                                            <Input
                                                id="address.state"
                                                name="address[state]"
                                                defaultValue={
                                                    customer?.address?.state ??
                                                    ''
                                                }
                                                required
                                                className="min-h-11"
                                            />
                                            <InputError
                                                message={errors['address.state']}
                                            />
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
                                                customer?.address
                                                    ?.postal_code ?? ''
                                            }
                                            required
                                            className="min-h-11"
                                        />
                                        <InputError
                                            message={
                                                errors['address.postal_code']
                                            }
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
                                                customer?.address
                                                    ?.delivery_instructions ??
                                                ''
                                            }
                                            rows={3}
                                            className={selectClassName}
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    'address.delivery_instructions'
                                                ]
                                            }
                                        />
                                    </div>
                            </div>

                            <div
                                className={`space-y-4 ${!isEditing && step !== 3 ? 'hidden' : ''}`}
                            >
                                    <label className="flex items-center gap-3 text-sm">
                                        <input
                                            type="checkbox"
                                            name="portal[create]"
                                            value="1"
                                            checked={createPortal}
                                            onChange={(event) =>
                                                setCreatePortal(
                                                    event.target.checked,
                                                )
                                            }
                                            className="size-4 rounded border"
                                        />
                                        Create portal login for this customer
                                    </label>

                                    {createPortal && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="portal.password">
                                                Portal password
                                            </Label>
                                            <PasswordInput
                                                id="portal.password"
                                                name="portal[password]"
                                                required
                                                autoComplete="new-password"
                                                className="min-h-11"
                                            />
                                            <InputError
                                                message={
                                                    errors['portal.password']
                                                }
                                            />
                                        </div>
                                    )}
                            </div>

                            {isEditing &&
                                can?.createPortal &&
                                !customer.has_portal_account && (
                                    <div className="space-y-4 rounded-lg border p-4">
                                        <label className="flex items-center gap-3 text-sm">
                                            <input
                                                type="checkbox"
                                                name="portal[create]"
                                                value="1"
                                                className="size-4 rounded border"
                                            />
                                            Create portal login
                                        </label>
                                        <div className="grid gap-2">
                                            <Label htmlFor="portal.password">
                                                Portal password
                                            </Label>
                                            <PasswordInput
                                                id="portal.password"
                                                name="portal[password]"
                                                autoComplete="new-password"
                                                className="min-h-11"
                                            />
                                            <InputError
                                                message={
                                                    errors['portal.password']
                                                }
                                            />
                                        </div>
                                    </div>
                                )}

                            <div className="flex flex-col gap-3 sm:flex-row">
                                {!isEditing && step > 1 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() =>
                                            setStep((current) => current - 1)
                                        }
                                    >
                                        Back
                                    </Button>
                                )}

                                {!isEditing && step < totalSteps ? (
                                    <Button
                                        type="button"
                                        className="min-h-11"
                                        onClick={() =>
                                            setStep((current) => current + 1)
                                        }
                                    >
                                        Continue
                                    </Button>
                                ) : (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="min-h-11"
                                    >
                                        {submitLabel}
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
