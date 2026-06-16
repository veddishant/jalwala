import { useEffect, useState, type ComponentType } from 'react';

export function ClientToaster() {
    const [Toaster, setToaster] = useState<ComponentType | null>(null);

    useEffect(() => {
        void import('@/components/ui/sonner').then((module) => {
            setToaster(() => module.Toaster);
        });
    }, []);

    if (!Toaster) {
        return null;
    }

    return <Toaster />;
}
