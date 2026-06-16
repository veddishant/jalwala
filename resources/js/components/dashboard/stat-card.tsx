import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type StatCardProps = {
    label: string;
    value: string | number;
    description?: string;
    icon: LucideIcon;
    href?: string;
    valueClassName?: string;
};

export function StatCard({
    label,
    value,
    description,
    icon: Icon,
    href,
    valueClassName,
}: StatCardProps) {
    const content = (
        <Card className={cn(href && 'transition-colors hover:border-primary/40 hover:bg-primary/5')}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardDescription>{label}</CardDescription>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <CardTitle
                    className={cn('text-3xl font-semibold tabular-nums', valueClassName)}
                >
                    {value}
                </CardTitle>
                {description && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );

    if (href) {
        return (
            <Link href={href} className="block">
                {content}
            </Link>
        );
    }

    return content;
}
