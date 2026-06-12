import UserForm from './user-form';
import { create, index } from '@/routes/admin/users';

export default function CreateUser({
    roles,
    statuses,
}: {
    roles: Array<{ name: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
}) {
    return (
        <UserForm
            title="Create user"
            description="Add a delivery agent, customer portal account, or team member."
            roles={roles}
            statuses={statuses}
            submitLabel="Create user"
        />
    );
}

CreateUser.layout = {
    breadcrumbs: [
        { title: 'Users', href: index() },
        { title: 'Create', href: create() },
    ],
};
