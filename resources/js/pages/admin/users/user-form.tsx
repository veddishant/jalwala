import { Form, Head } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    role: string | null;
};

export default function UserForm({
    title,
    description,
    user,
    roles,
    statuses,
    submitLabel,
}: {
    title: string;
    description: string;
    user?: ManagedUser;
    roles: Array<{ name: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
    submitLabel: string;
}) {
    const isEditing = user !== undefined;

    return (
        <>
            <Head title={title} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <Heading title={title} description={description} />

                <Form
                    {...(isEditing
                        ? UserController.update.form(user.id)
                        : UserController.store.form())}
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
                                    defaultValue={user?.name}
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
                                    defaultValue={user?.email}
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
                                    defaultValue={user?.phone ?? ''}
                                    autoComplete="tel"
                                    className="min-h-11"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {isEditing
                                        ? 'New password (optional)'
                                        : 'Password'}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required={!isEditing}
                                    autoComplete="new-password"
                                    className="min-h-11"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">Role</Label>
                                <select
                                    id="role"
                                    name="role"
                                    defaultValue={user?.role ?? roles[0]?.name}
                                    required
                                    className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                >
                                    {roles.map((role) => (
                                        <option key={role.name} value={role.name}>
                                            {role.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.role} />
                            </div>

                            {isEditing && (
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        name="status"
                                        defaultValue={user.status}
                                        required
                                        className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
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
                            )}

                            <Button
                                type="submit"
                                disabled={processing}
                                className="min-h-11 w-full sm:w-auto"
                            >
                                {submitLabel}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
