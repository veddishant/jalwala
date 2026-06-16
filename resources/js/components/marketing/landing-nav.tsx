import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { LogoMark } from '@/components/logo-mark';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dashboard, login } from '@/routes';
import { register as supplierRegister } from '@/routes/supplier';

const navLinks = [
    { label: 'Features', href: '#features' },
    { label: 'How it works', href: '#how-it-works' },
    { label: 'About', href: '#about' },
    { label: 'Contact', href: '#contact' },
];

export function LandingNav() {
    const { auth, name } = usePage<{ auth: { user?: unknown }; name: string }>()
        .props;
    const [open, setOpen] = useState(false);

    const authLinks = auth.user ? (
        <Button asChild className="min-h-11">
            <Link href={dashboard()}>Dashboard</Link>
        </Button>
    ) : (
        <>
            <Button asChild variant="ghost" className="min-h-11">
                <Link href={login()}>Log in</Link>
            </Button>
            <Button asChild className="min-h-11 shadow-lg shadow-primary/25">
                <Link href={supplierRegister()}>Start free trial</Link>
            </Button>
        </>
    );

    return (
        <header className="sticky top-0 z-50 border-b border-border/40 bg-background/80 backdrop-blur-xl">
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 md:px-6">
                <Link href="/" className="flex items-center gap-2.5">
                    <LogoMark className="size-9 rounded-xl" />
                    <span className="text-lg font-semibold tracking-tight">
                        {name}
                    </span>
                </Link>

                <nav className="hidden items-center gap-8 md:flex">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>

                <div className="hidden items-center gap-2 md:flex">
                    {authLinks}
                </div>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild className="md:hidden">
                        <Button variant="outline" size="icon" aria-label="Menu">
                            <Menu />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" className="w-[min(100vw-2rem,20rem)]">
                        <SheetHeader>
                            <SheetTitle className="text-left">{name}</SheetTitle>
                        </SheetHeader>
                        <nav className="mt-8 flex flex-col gap-4">
                            {navLinks.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setOpen(false)}
                                    className="text-base font-medium"
                                >
                                    {link.label}
                                </a>
                            ))}
                            <div className="mt-4 flex flex-col gap-3 border-t pt-6">
                                {auth.user ? (
                                    <Button asChild className="min-h-11 w-full">
                                        <Link
                                            href={dashboard()}
                                            onClick={() => setOpen(false)}
                                        >
                                            Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="min-h-11 w-full"
                                        >
                                            <Link
                                                href={login()}
                                                onClick={() => setOpen(false)}
                                            >
                                                Log in
                                            </Link>
                                        </Button>
                                        <Button asChild className="min-h-11 w-full">
                                            <Link
                                                href={supplierRegister()}
                                                onClick={() => setOpen(false)}
                                            >
                                                Start free trial
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}
