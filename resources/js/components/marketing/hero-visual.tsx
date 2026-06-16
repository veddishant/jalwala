import {
    BarChart3,
    Droplets,
    Package,
    Truck,
    Users,
    Wallet,
} from 'lucide-react';
import { LogoMark } from '@/components/logo-mark';

export function HeroVisual() {
    return (
        <div className="relative mx-auto w-full max-w-lg lg:max-w-none">
            <div className="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-sky-400/20 via-cyan-300/10 to-blue-500/20 blur-2xl" />

            <div className="relative overflow-hidden rounded-3xl border border-white/20 bg-gradient-to-b from-sky-50 to-white p-4 shadow-2xl shadow-sky-500/10 dark:from-sky-950/40 dark:to-background dark:shadow-sky-900/20">
                <div className="mb-4 flex items-center justify-between rounded-2xl bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:bg-card/80">
                    <div className="flex items-center gap-2">
                        <LogoMark className="size-8 rounded-lg" />
                        <div className="space-y-1">
                            <div className="h-2 w-20 rounded-full bg-foreground/10" />
                            <div className="h-2 w-14 rounded-full bg-foreground/5" />
                        </div>
                    </div>
                    <div className="flex gap-1.5">
                        <span className="size-2.5 rounded-full bg-red-400/80" />
                        <span className="size-2.5 rounded-full bg-amber-400/80" />
                        <span className="size-2.5 rounded-full bg-emerald-400/80" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div className="col-span-2 rounded-2xl bg-gradient-to-br from-sky-500 to-cyan-600 p-4 text-white shadow-lg">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="text-xs font-medium text-sky-100">
                                    Today&apos;s deliveries
                                </p>
                                <p className="mt-1 text-3xl font-bold">48</p>
                                <p className="mt-1 text-xs text-sky-100/90">
                                    12 routes active
                                </p>
                            </div>
                            <div className="rounded-xl bg-white/15 p-2.5">
                                <Truck className="size-6" />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-white/90 p-3 dark:bg-card/90">
                        <Wallet className="mb-2 size-5 text-sky-600" />
                        <p className="text-xs text-muted-foreground">Wallet</p>
                        <p className="text-lg font-semibold">₹1.2L</p>
                    </div>

                    <div className="rounded-2xl border bg-white/90 p-3 dark:bg-card/90">
                        <Users className="mb-2 size-5 text-cyan-600" />
                        <p className="text-xs text-muted-foreground">
                            Customers
                        </p>
                        <p className="text-lg font-semibold">326</p>
                    </div>

                    <div className="rounded-2xl border bg-white/90 p-3 dark:bg-card/90">
                        <Package className="mb-2 size-5 text-blue-600" />
                        <p className="text-xs text-muted-foreground">Jars out</p>
                        <p className="text-lg font-semibold">892</p>
                    </div>

                    <div className="rounded-2xl border bg-white/90 p-3 dark:bg-card/90">
                        <BarChart3 className="mb-2 size-5 text-indigo-600" />
                        <p className="text-xs text-muted-foreground">Revenue</p>
                        <p className="text-lg font-semibold">+18%</p>
                    </div>
                </div>

                <div className="mt-3 flex items-center gap-3 rounded-2xl border bg-white/90 p-3 dark:bg-card/90">
                    <div className="flex size-10 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/50">
                        <Droplets className="size-5 text-sky-600" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">
                            Subscription · Priya Sharma
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Mon, Wed, Fri · 20L jar
                        </p>
                    </div>
                    <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Active
                    </span>
                </div>
            </div>

            <div className="absolute -right-2 -bottom-2 hidden rounded-2xl border bg-background p-3 shadow-xl lg:block">
                <p className="text-xs font-medium text-muted-foreground">
                    Low balance alert
                </p>
                <p className="text-sm font-semibold">3 customers</p>
            </div>
        </div>
    );
}
