import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Building2,
    CalendarClock,
    CheckCircle2,
    Droplets,
    LineChart,
    Package,
    RefreshCw,
    Shield,
    Smartphone,
    Truck,
    Users,
    Wallet,
} from 'lucide-react';
import { HeroVisual } from '@/components/marketing/hero-visual';
import { LandingNav } from '@/components/marketing/landing-nav';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { login } from '@/routes';
import { register as supplierRegister } from '@/routes/supplier';

const features = [
    {
        icon: Users,
        title: 'Customer management',
        description:
            'Onboard households and businesses, manage addresses, pause accounts, and give every customer a branded portal.',
        color: 'text-sky-600 bg-sky-100 dark:bg-sky-900/40',
    },
    {
        icon: Wallet,
        title: 'Wallet & billing',
        description:
            'Prepaid wallets, top-ups, low-balance alerts, and automatic debits when orders are confirmed.',
        color: 'text-cyan-600 bg-cyan-100 dark:bg-cyan-900/40',
    },
    {
        icon: Package,
        title: 'Jar deposits & inventory',
        description:
            'Track returnable jars at warehouse and customer premises. Collect deposits and reconcile empties on delivery.',
        color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/40',
    },
    {
        icon: CalendarClock,
        title: 'Orders & subscriptions',
        description:
            'One-off orders or recurring weekly schedules with vacation pause, auto-generation, and status tracking.',
        color: 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/40',
    },
    {
        icon: Truck,
        title: 'Delivery operations',
        description:
            'Assign routes, update delivery status from mobile, and keep agents focused on today’s run sheet.',
        color: 'text-violet-600 bg-violet-100 dark:bg-violet-900/40',
    },
    {
        icon: LineChart,
        title: 'Reports & insights',
        description:
            'Sales, consumption, wallet, deposits, and agent performance — filter by date and export to CSV.',
        color: 'text-teal-600 bg-teal-100 dark:bg-teal-900/40',
    },
];

const steps = [
    {
        step: '01',
        title: 'Launch your supplier workspace',
        description:
            'Sign up, add products and pricing, and invite your team in minutes.',
    },
    {
        step: '02',
        title: 'Onboard customers',
        description:
            'Create accounts, collect jar deposits, and share a portal link for self-service.',
    },
    {
        step: '03',
        title: 'Deliver & grow',
        description:
            'Run daily orders, subscriptions, and deliveries — with wallets and inventory staying in sync.',
    },
];

const highlights = [
    { icon: Building2, label: 'Multi-tenant SaaS' },
    { icon: Smartphone, label: 'Mobile-first UI' },
    { icon: Shield, label: 'Role-based access' },
    { icon: RefreshCw, label: 'Real-time sync' },
];

export default function Welcome() {
    const { name } = usePage<{ name: string }>().props;

    return (
        <>
            <Head title={`${name} — Water delivery, simplified`} />

            <div className="min-h-screen bg-background text-foreground">
                <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
                    <div className="absolute -top-40 left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-sky-400/20 blur-3xl" />
                    <div className="absolute top-1/3 -right-20 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-64 w-64 rounded-full bg-blue-400/10 blur-3xl" />
                </div>

                <LandingNav />

                <main>
                    {/* Hero */}
                    <section className="mx-auto grid max-w-6xl items-center gap-12 px-4 py-14 md:px-6 md:py-20 lg:grid-cols-2 lg:gap-16 lg:py-24">
                        <div className="flex flex-col gap-8 text-center lg:text-left">
                            <div className="inline-flex w-fit items-center gap-2 self-center rounded-full border border-sky-200 bg-sky-50 px-4 py-1.5 text-sm font-medium text-sky-800 lg:self-start dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-200">
                                <Droplets className="size-4" />
                                Built for water & jar delivery businesses
                            </div>

                            <div className="space-y-4">
                                <h1 className="text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                    Run your water supply on{' '}
                                    <span className="bg-gradient-to-r from-sky-600 to-cyan-500 bg-clip-text text-transparent">
                                        one modern platform
                                    </span>
                                </h1>
                                <p className="mx-auto max-w-xl text-lg text-muted-foreground text-pretty lg:mx-0">
                                    {name} helps suppliers manage customers,
                                    wallets, jar deposits, subscriptions, and
                                    deliveries — while customers order and pay
                                    from a simple portal.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                                <Button
                                    asChild
                                    size="lg"
                                    className="min-h-12 px-8 text-base shadow-lg shadow-primary/20"
                                >
                                    <Link href={supplierRegister()}>
                                        Start free trial
                                        <ArrowRight />
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="lg"
                                    className="min-h-12 px-8 text-base"
                                >
                                    <Link href={login()}>Supplier log in</Link>
                                </Button>
                            </div>

                            <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-muted-foreground lg:justify-start">
                                {highlights.map(({ icon: Icon, label }) => (
                                    <span
                                        key={label}
                                        className="inline-flex items-center gap-1.5"
                                    >
                                        <Icon className="size-4 text-sky-600" />
                                        {label}
                                    </span>
                                ))}
                            </div>
                        </div>

                        <HeroVisual />
                    </section>

                    {/* Value strip */}
                    <section className="border-y border-border/60 bg-muted/30">
                        <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-3 md:px-6">
                            {[
                                {
                                    value: 'All-in-one',
                                    label: 'Customers, wallet, orders & inventory',
                                },
                                {
                                    value: 'Portal',
                                    label: 'Self-service for your end customers',
                                },
                                {
                                    value: 'SaaS ready',
                                    label: 'Scale from one supplier to many',
                                },
                            ].map((item) => (
                                <div
                                    key={item.value}
                                    className="text-center sm:text-left"
                                >
                                    <p className="text-2xl font-bold tracking-tight text-sky-700 dark:text-sky-300">
                                        {item.value}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {item.label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Features */}
                    <section
                        id="features"
                        className="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 md:px-6 md:py-24"
                    >
                        <div className="mx-auto mb-12 max-w-2xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Everything you need to deliver water
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                From first jar deposit to monthly subscription
                                renewals — designed for Indian water suppliers
                                and their customers.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature) => {
                                const Icon = feature.icon;

                                return (
                                    <Card
                                        key={feature.title}
                                        className="border-border/60 bg-card/50 backdrop-blur transition-shadow hover:shadow-lg hover:shadow-sky-500/5"
                                    >
                                        <CardHeader>
                                            <div
                                                className={`mb-2 flex size-11 items-center justify-center rounded-xl ${feature.color}`}
                                            >
                                                <Icon className="size-5" />
                                            </div>
                                            <CardTitle className="text-lg">
                                                {feature.title}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <CardDescription className="text-base leading-relaxed">
                                                {feature.description}
                                            </CardDescription>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>

                    {/* How it works */}
                    <section
                        id="how-it-works"
                        className="scroll-mt-20 bg-muted/40 py-16 md:py-24"
                    >
                        <div className="mx-auto max-w-6xl px-4 md:px-6">
                            <div className="mx-auto mb-12 max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Up and running in three steps
                                </h2>
                                <p className="mt-4 text-lg text-muted-foreground">
                                    No spreadsheets. No disconnected apps. One
                                    flow from signup to delivery.
                                </p>
                            </div>

                            <div className="grid gap-8 md:grid-cols-3">
                                {steps.map((item) => (
                                    <div
                                        key={item.step}
                                        className="relative rounded-2xl border bg-background p-6 shadow-sm"
                                    >
                                        <span className="text-4xl font-bold text-sky-200 dark:text-sky-900">
                                            {item.step}
                                        </span>
                                        <h3 className="mt-4 text-xl font-semibold">
                                            {item.title}
                                        </h3>
                                        <p className="mt-2 text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Audiences */}
                    <section
                        id="for-suppliers"
                        className="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 md:px-6 md:py-24"
                    >
                        <div className="grid gap-8 lg:grid-cols-2">
                            <Card className="overflow-hidden border-sky-200/60 bg-gradient-to-br from-sky-50 to-white dark:from-sky-950/30 dark:to-card">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-2xl">
                                        <Building2 className="size-6 text-sky-600" />
                                        For water suppliers
                                    </CardTitle>
                                    <CardDescription className="text-base">
                                        Admin dashboard, team roles, inventory,
                                        reports, and platform tools for growing
                                        your business.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {[
                                        'Supplier admin & delivery agent roles',
                                        'Customer onboarding with wallet setup',
                                        'Subscription schedules & auto orders',
                                        'CSV exports and delivery analytics',
                                    ].map((item) => (
                                        <p
                                            key={item}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-sky-600" />
                                            {item}
                                        </p>
                                    ))}
                                    <Button asChild className="mt-4 min-h-11 w-full sm:w-auto">
                                        <Link href={supplierRegister()}>
                                            Create supplier account
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="overflow-hidden border-cyan-200/60 bg-gradient-to-br from-cyan-50 to-white dark:from-cyan-950/20 dark:to-card">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-2xl">
                                        <Smartphone className="size-6 text-cyan-600" />
                                        For your customers
                                    </CardTitle>
                                    <CardDescription className="text-base">
                                        A branded portal where customers check
                                        balance, view deposits, place orders,
                                        and manage subscriptions.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {[
                                        'Wallet balance & transaction history',
                                        'Jar deposit summary',
                                        'Order placement & tracking',
                                        'Pause subscription when on vacation',
                                    ].map((item) => (
                                        <p
                                            key={item}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-cyan-600" />
                                            {item}
                                        </p>
                                    ))}
                                    <p className="pt-2 text-sm text-muted-foreground">
                                        Customers join via your unique portal
                                        link — ask your supplier for access.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    {/* CTA */}
                    <section className="mx-auto max-w-6xl px-4 pb-16 md:px-6 md:pb-24">
                        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-600 via-sky-500 to-cyan-500 px-6 py-12 text-center text-white shadow-2xl shadow-sky-500/25 md:px-12 md:py-16">
                            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_50%)]" />
                            <div className="relative mx-auto max-w-2xl">
                                <BarChart3 className="mx-auto mb-4 size-10 opacity-90" />
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Ready to modernize your water business?
                                </h2>
                                <p className="mt-4 text-lg text-sky-100">
                                    Join {name} with a 14-day trial. Set up
                                    products, onboard your first customers, and
                                    start delivering smarter.
                                </p>
                                <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="secondary"
                                        className="min-h-12 bg-white text-sky-700 hover:bg-white/90"
                                    >
                                        <Link href={supplierRegister()}>
                                            Get started free
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="outline"
                                        className="min-h-12 border-white/40 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border/60 bg-muted/20">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground md:flex-row md:px-6">
                        <p>
                            © {new Date().getFullYear()} {name}. Water delivery
                            management platform.
                        </p>
                        <div className="flex flex-wrap justify-center gap-6">
                            <a
                                href="#features"
                                className="hover:text-foreground"
                            >
                                Features
                            </a>
                            <Link
                                href={supplierRegister()}
                                className="hover:text-foreground"
                            >
                                Supplier signup
                            </Link>
                            <Link href={login()} className="hover:text-foreground">
                                Log in
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
