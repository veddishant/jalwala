import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type StatCardAccent =
    | 'sky'
    | 'violet'
    | 'emerald'
    | 'cyan'
    | 'amber'
    | 'rose'
    | 'slate';

type StatCardProps = {
    label: string;
    value: string | number;
    description?: string;
    icon: LucideIcon;
    href?: string;
    valueClassName?: string;
    accent?: StatCardAccent;
};

const accentStyles: Record<
    StatCardAccent,
    { orb: string; icon: string; iconText: string }
> = {
    sky: {
        orb: 'from-sky-500/30 via-cyan-400/10 to-transparent',
        icon: 'from-sky-500/20 to-cyan-500/10 ring-sky-500/20',
        iconText: 'text-sky-600 dark:text-sky-400',
    },
    violet: {
        orb: 'from-violet-500/30 via-purple-400/10 to-transparent',
        icon: 'from-violet-500/20 to-purple-500/10 ring-violet-500/20',
        iconText: 'text-violet-600 dark:text-violet-400',
    },
    emerald: {
        orb: 'from-emerald-500/30 via-green-400/10 to-transparent',
        icon: 'from-emerald-500/20 to-green-500/10 ring-emerald-500/20',
        iconText: 'text-emerald-600 dark:text-emerald-400',
    },
    cyan: {
        orb: 'from-cyan-500/30 via-teal-400/10 to-transparent',
        icon: 'from-cyan-500/20 to-teal-500/10 ring-cyan-500/20',
        iconText: 'text-cyan-600 dark:text-cyan-400',
    },
    amber: {
        orb: 'from-amber-500/30 via-orange-400/10 to-transparent',
        icon: 'from-amber-500/20 to-orange-500/10 ring-amber-500/20',
        iconText: 'text-amber-600 dark:text-amber-400',
    },
    rose: {
        orb: 'from-rose-500/30 via-pink-400/10 to-transparent',
        icon: 'from-rose-500/20 to-pink-500/10 ring-rose-500/20',
        iconText: 'text-rose-600 dark:text-rose-400',
    },
    slate: {
        orb: 'from-foreground/10 via-foreground/5 to-transparent',
        icon: 'from-muted to-muted/50 ring-border/60',
        iconText: 'text-muted-foreground',
    },
};

export function StatCard({
    label,
    value,
    description,
    icon: Icon,
    href,
    valueClassName,
    accent = 'slate',
}: StatCardProps) {
    const styles = accentStyles[accent];

    const content = (
        <Card
            className={cn(
                'group relative h-full overflow-hidden border-0 py-0 shadow-sm ring-1 ring-border/60 transition-all duration-300',
                href &&
                    'hover:-translate-y-0.5 hover:shadow-md hover:ring-primary/25',
            )}
        >
            <div
                className={cn(
                    'pointer-events-none absolute -top-8 -right-8 size-32 rounded-full bg-gradient-to-br blur-2xl',
                    styles.orb,
                )}
            />

            <CardContent className="relative flex h-full flex-col gap-5 p-5">
                <div className="flex items-start justify-between gap-3">
                    <div
                        className={cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-1',
                            styles.icon,
                        )}
                    >
                        <Icon className={cn('size-5', styles.iconText)} />
                    </div>

                    {href && (
                        <ArrowUpRight className="size-4 shrink-0 text-muted-foreground/0 transition-all duration-300 group-hover:text-muted-foreground" />
                    )}
                </div>

                <div className="mt-auto space-y-1">
                    <p
                        className={cn(
                            'text-3xl font-bold tracking-tight tabular-nums',
                            valueClassName,
                        )}
                    >
                        {value}
                    </p>
                    <p className="text-sm font-medium leading-snug text-foreground">
                        {label}
                    </p>
                    <p
                        className={cn(
                            'min-h-4 text-xs leading-relaxed',
                            description
                                ? 'text-muted-foreground'
                                : 'invisible select-none',
                        )}
                        aria-hidden={!description}
                    >
                        {description ?? '\u00a0'}
                    </p>
                </div>
            </CardContent>
        </Card>
    );

    if (href) {
        return (
            <Link href={href} className="block h-full">
                {content}
            </Link>
        );
    }

    return content;
}
