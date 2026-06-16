import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

type LogoMarkProps = {
    className?: string;
    iconClassName?: string;
};

export function LogoMark({ className, iconClassName }: LogoMarkProps) {
    return (
        <div
            className={cn(
                'flex shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-cyan-600 text-white shadow-sm shadow-sky-500/25',
                className,
            )}
        >
            <AppLogoIcon
                className={cn('size-[62%] fill-current text-white', iconClassName)}
            />
        </div>
    );
}
