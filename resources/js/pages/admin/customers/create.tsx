import CustomerForm from './customer-form';
import { create, index } from '@/routes/admin/customers';

export default function CreateCustomer({
    statuses,
}: {
    statuses: Array<{ value: string; label: string }>;
}) {
    return (
        <CustomerForm
            title="Onboard customer"
            description="Capture customer details, delivery address, and optional portal access."
            statuses={statuses}
            submitLabel="Create customer"
        />
    );
}

CreateCustomer.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Create', href: create() },
    ],
};
