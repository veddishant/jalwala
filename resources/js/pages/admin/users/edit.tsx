import UserForm from './user-form';
import { edit, index } from '@/routes/admin/users';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    role: string | null;
};

export default function EditUser({
    user,
    roles,
    statuses,
}: {
    user: ManagedUser;
    roles: Array<{ name: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
}) {
    return (
        <UserForm
            title="Edit user"
            description="Update account details and role assignment."
            user={user}
            roles={roles}
            statuses={statuses}
            submitLabel="Save changes"
        />
    );
}

EditUser.layout = {
    breadcrumbs: [
        { title: 'Users', href: index() },
        { title: 'Edit', href: edit(0) },
    ],
};
