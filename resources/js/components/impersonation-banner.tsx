import { Form, usePage } from '@inertiajs/react';
import ImpersonationController from '@/actions/App/Http/Controllers/Platform/ImpersonationController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { LogOut, UserCog } from 'lucide-react';

type ImpersonationProps = {
    active: boolean;
    tenant: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

export function ImpersonationBanner() {
    const { impersonation } = usePage<{ impersonation: ImpersonationProps }>()
        .props;

    if (!impersonation.active || !impersonation.tenant) {
        return null;
    }

    return (
        <Alert className="rounded-none border-x-0 border-t-0 border-amber-500/50 bg-amber-500/10">
            <UserCog className="size-4 text-amber-600" />
            <AlertDescription className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Support mode: viewing{' '}
                    <strong>{impersonation.tenant.name}</strong> (
                    {impersonation.tenant.slug})
                </span>
                <Form {...ImpersonationController.destroy.form()}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            disabled={processing}
                            className="min-h-9 shrink-0"
                        >
                            <LogOut className="size-4" />
                            Exit support mode
                        </Button>
                    )}
                </Form>
            </AlertDescription>
        </Alert>
    );
}
