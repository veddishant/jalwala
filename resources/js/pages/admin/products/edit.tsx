import { Link } from '@inertiajs/react';
import ProductForm from './product-form';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/admin/products';

type ManagedProduct = {
    id: number;
    name: string;
    sku: string;
    type: string;
    capacity_liters: string | null;
    unit_price: string;
    deposit_amount: string;
    is_returnable: boolean;
    status: string;
};

export default function EditProduct({
    product,
    statuses,
    types,
}: {
    product: ManagedProduct;
    statuses: Array<{ value: string; label: string }>;
    types: Array<{ value: string; label: string }>;
}) {
    return (
        <>
            <ProductForm
                title={`Edit ${product.name}`}
                description={`SKU: ${product.sku}`}
                product={product}
                statuses={statuses}
                types={types}
                submitLabel="Save changes"
            />

            <div className="mx-auto w-full max-w-2xl px-4 pb-6 md:px-6">
                <Button asChild variant="outline" className="min-h-11">
                    <Link href={index()}>Back to products</Link>
                </Button>
            </div>
        </>
    );
}

EditProduct.layout = {
    breadcrumbs: [
        { title: 'Products', href: index() },
        { title: 'Edit', href: edit(1) },
    ],
};
