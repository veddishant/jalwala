import ProductForm from './product-form';
import { create, index } from '@/routes/admin/products';

export default function CreateProduct({
    statuses,
    types,
}: {
    statuses: Array<{ value: string; label: string }>;
    types: Array<{ value: string; label: string }>;
}) {
    return (
        <ProductForm
            title="Add product"
            description="Define catalog item pricing and returnable deposit amounts."
            statuses={statuses}
            types={types}
            submitLabel="Create product"
        />
    );
}

CreateProduct.layout = {
    breadcrumbs: [
        { title: 'Products', href: index() },
        { title: 'Create', href: create() },
    ],
};
