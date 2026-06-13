import { Form, Head, Link } from '@inertiajs/react';
import CustomerForm from './customer-form';
import CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { deposits, edit, index, inventory, wallet } from '@/routes/admin/customers';

type ManagedCustomer = {
    id: number;
    code: string;
    name: string;
    phone: string;
    email: string | null;
    status: string;
    notes: string | null;
    has_portal_account: boolean;
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

export default function EditCustomer({
    customer,
    statuses,
    can,
}: {
    customer: ManagedCustomer;
    statuses: Array<{ value: string; label: string }>;
    can: {
        manageAddresses: boolean;
        createPortal: boolean;
    };
}) {
    const isClosed = customer.status === 'closed';
    const isActive = customer.status === 'active';
    const isPaused = customer.status === 'paused';

    return (
        <>
            <Head title={`Edit ${customer.name}`} />

            <div className="mx-auto flex w-full max-w-2xl flex-wrap gap-2 px-4 pt-4 md:px-6">
                <Button asChild variant="outline" className="min-h-10">
                    <Link href={wallet(customer.id)}>View wallet</Link>
                </Button>
                <Button asChild variant="outline" className="min-h-10">
                    <Link href={deposits(customer.id)}>View deposits</Link>
                </Button>
                <Button asChild variant="outline" className="min-h-10">
                    <Link href={inventory(customer.id)}>View inventory</Link>
                </Button>
            </div>

            {!isClosed && (
                <div className="mx-auto flex w-full max-w-2xl flex-wrap gap-2 px-4 md:px-6">
                    {isActive && (
                        <Form
                            {...CustomerController.pause.form(customer.id)}
                            options={{ preserveScroll: true }}
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                className="min-h-10"
                            >
                                Pause customer
                            </Button>
                        </Form>
                    )}
                    {isPaused && (
                        <Form
                            {...CustomerController.resume.form(customer.id)}
                            options={{ preserveScroll: true }}
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                className="min-h-10"
                            >
                                Resume customer
                            </Button>
                        </Form>
                    )}
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive" className="min-h-10">
                                Close customer
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Close customer</DialogTitle>
                                <DialogDescription>
                                    This will deactivate portal access and mark
                                    the account as closed.
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                {...CustomerController.close.form(customer.id)}
                                options={{ preserveScroll: true }}
                                className="space-y-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="closure_reason">
                                        Closure reason (optional)
                                    </Label>
                                    <Input
                                        id="closure_reason"
                                        name="closure_reason"
                                        className="min-h-11"
                                    />
                                </div>
                                <DialogFooter>
                                    <Button type="submit" variant="destructive">
                                        Confirm closure
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            )}

            <CustomerForm
                title={`Edit ${customer.name}`}
                description={`Customer code: ${customer.code}`}
                customer={customer}
                statuses={statuses}
                can={can}
                submitLabel="Save changes"
            />

            <div className="mx-auto w-full max-w-2xl px-4 pb-6 md:px-6">
                <Button asChild variant="outline" className="min-h-11">
                    <Link href={index()}>Back to customers</Link>
                </Button>
            </div>
        </>
    );
}

EditCustomer.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Edit', href: edit(1) },
    ],
};
