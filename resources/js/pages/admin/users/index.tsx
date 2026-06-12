import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create, edit, index } from '@/routes/admin/users';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    role: string | null;
    roles: string[];
};

type PaginatedUsers = {
    data: ManagedUser[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const roleVariant = (role: string | null) => {
    switch (role) {
        case 'supplier-admin':
            return 'default' as const;
        case 'delivery-agent':
            return 'secondary' as const;
        case 'customer':
            return 'outline' as const;
        default:
            return 'outline' as const;
    }
};

export default function UsersIndex({
    users,
    can,
}: {
    users: PaginatedUsers;
    can: { create: boolean };
}) {
    return (
        <>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Users"
                        description="Manage team members, delivery agents, and customer portal accounts."
                    />
                    {can.create && (
                        <Button asChild className="min-h-11 w-full sm:w-auto">
                            <Link href={create()}>
                                <Plus className="size-4" />
                                Add user
                            </Link>
                        </Button>
                    )}
                </div>

                {users.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <Users className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                No users yet. Create your first team member.
                            </p>
                            {can.create && (
                                <Button asChild>
                                    <Link href={create()}>Add user</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {users.data.map((user) => (
                            <Card key={user.id}>
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-3">
                                    <div className="min-w-0 space-y-1">
                                        <CardTitle className="truncate text-base">
                                            {user.name}
                                        </CardTitle>
                                        <CardDescription className="truncate">
                                            {user.email}
                                        </CardDescription>
                                    </div>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="min-h-9 shrink-0"
                                    >
                                        <Link href={edit(user.id)}>
                                            <Pencil className="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center gap-2 pt-0">
                                    {user.role && (
                                        <Badge variant={roleVariant(user.role)}>
                                            {user.role.replace('-', ' ')}
                                        </Badge>
                                    )}
                                    <Badge
                                        variant={
                                            user.status === 'active'
                                                ? 'secondary'
                                                : 'destructive'
                                        }
                                    >
                                        {user.status}
                                    </Badge>
                                    {user.phone && (
                                        <span className="text-sm text-muted-foreground">
                                            {user.phone}
                                        </span>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {users.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {users.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={`${link.label}-${index}`}
                                    asChild
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    className="min-h-9"
                                >
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={`${link.label}-${index}`}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    className="min-h-9"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Users', href: index() },
    ],
};
