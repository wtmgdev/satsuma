import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    Building2,
    Gauge,
    Globe,
    ScanSearch,
    ShieldAlert,
    Sparkles,
    Zap,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

function BentoCell({
    icon: Icon,
    title,
    children,
    className = '',
}: {
    icon: typeof Globe;
    title: string;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div
            className={`flex flex-col gap-3 rounded-xl border border-border/40 bg-card/50 p-5 ${className}`}
        >
            <span className="flex items-center gap-2.5">
                <span className="flex size-8 items-center justify-center rounded-md bg-muted">
                    <Icon className="size-4 text-orange-400" />
                </span>
                <span className="text-sm font-semibold">{title}</span>
            </span>
            <div className="flex flex-1 flex-col gap-3">{children}</div>
        </div>
    );
}

const cellCopy = 'text-sm leading-relaxed text-muted-foreground';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Satsuma — WordPress management" />
            <div className="dark relative min-h-screen bg-background text-foreground">
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{
                        background:
                            'radial-gradient(ellipse 60rem 30rem at 50% -14rem, color-mix(in oklab, oklch(0.72 0.15 55) 18%, transparent), transparent)',
                    }}
                />

                <div className="relative flex min-h-screen flex-col">
                    <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
                        <div className="flex items-center gap-2.5">
                            <AppLogoIcon className="size-7 text-orange-400" />
                            <span className="text-sm font-semibold tracking-tight">
                                Satsuma
                            </span>
                        </div>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button size="sm" asChild>
                                    <Link href={dashboard()}>
                                        Dashboard
                                        <ArrowRight data-icon="inline-end" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="sm" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                            )}
                        </nav>
                    </header>

                    <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col px-6">
                        <section className="flex flex-col items-center gap-6 py-24 text-center md:py-32">
                            <h1 className="max-w-3xl text-balance text-4xl font-semibold tracking-tight md:text-6xl">
                                Every WordPress site.
                                <br />
                                <span className="text-muted-foreground">
                                    One calm dashboard.
                                </span>
                            </h1>
                            <p className="max-w-xl text-balance text-base text-muted-foreground md:text-lg">
                                Satsuma keeps track of versions, updates,
                                security and performance across every site you
                                manage — so nothing drifts out of date
                                unnoticed.
                            </p>
                            <div className="flex items-center gap-3">
                                {auth.user ? (
                                    <Button asChild>
                                        <Link href={dashboard()}>
                                            Open dashboard
                                            <ArrowRight data-icon="inline-end" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button asChild>
                                        <Link href={login()}>
                                            Log in
                                            <ArrowRight data-icon="inline-end" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </section>

                        <section className="grid gap-3 pb-24 md:grid-cols-6">
                            <BentoCell
                                icon={ScanSearch}
                                title="See everything, miss nothing"
                                className="md:col-span-4"
                            >
                                <p className={cellCopy}>
                                    Install the agent once and every scan pulls
                                    the full picture: WordPress and PHP
                                    versions, every plugin and theme, active or
                                    not — including premium plugins that hide
                                    from ordinary update checks.
                                </p>
                                <div className="mt-auto flex flex-col gap-1.5 rounded-lg border border-border/40 bg-background/50 p-3 text-xs">
                                    <span className="flex items-center justify-between">
                                        <span className="font-medium">
                                            Advanced Custom Fields PRO
                                        </span>
                                        <span className="text-muted-foreground tabular-nums">
                                            6.7.0 → 6.8.10
                                        </span>
                                    </span>
                                    <span className="flex items-center justify-between">
                                        <span className="font-medium">
                                            WordPress Importer
                                        </span>
                                        <span className="text-muted-foreground tabular-nums">
                                            0.9.5 → 0.9.7
                                        </span>
                                    </span>
                                </div>
                            </BentoCell>

                            <BentoCell
                                icon={ShieldAlert}
                                title="Know which updates can wait"
                                className="md:col-span-2"
                            >
                                <p className={cellCopy}>
                                    Every installed version is checked against
                                    known vulnerabilities — so a critical CVE
                                    never hides inside a routine changelog.
                                </p>
                                <div className="mt-auto flex flex-wrap gap-1.5">
                                    <Badge variant="destructive">1 critical</Badge>
                                    <Badge variant="warning">2 high</Badge>
                                    <Badge variant="outline">CVSS scored</Badge>
                                </div>
                            </BentoCell>

                            <BentoCell
                                icon={Bell}
                                title="Find out before your client does"
                                className="md:col-span-2"
                            >
                                <p className={cellCopy}>
                                    Sites are pinged around the clock. When one
                                    goes down — or comes back — your whole team
                                    hears about it, in-app and by email.
                                </p>
                                <div className="mt-auto flex items-center gap-2 rounded-lg border border-border/40 bg-background/50 p-3 text-xs">
                                    <span className="size-2 shrink-0 rounded-full bg-emerald-500" />
                                    botniska.wtmgdev.com is back online
                                </div>
                            </BentoCell>

                            <BentoCell
                                icon={Gauge}
                                title="Performance, explained"
                                className="md:col-span-2"
                            >
                                <p className={cellCopy}>
                                    Real Lighthouse audits with an AI summary
                                    that tells you what to fix first — in plain
                                    language, not audit jargon.
                                </p>
                                <div className="mt-auto flex items-center gap-3 text-xs">
                                    <span className="font-semibold text-amber-500 tabular-nums">
                                        71
                                    </span>
                                    <span className="text-muted-foreground">
                                        Performance · mobile
                                    </span>
                                </div>
                            </BentoCell>

                            <BentoCell
                                icon={Globe}
                                title="Domains that never lapse"
                                className="md:col-span-2"
                            >
                                <p className={cellCopy}>
                                    Registrar, registration and expiry dates,
                                    nameservers — refreshed daily, so a lapsed
                                    domain never takes a client site with it.
                                </p>
                                <div className="mt-auto flex items-center justify-between rounded-lg border border-border/40 bg-background/50 p-3 text-xs">
                                    <span className="text-muted-foreground">
                                        wtmgdev.com
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        expires Dec 9, 2026
                                    </span>
                                </div>
                            </BentoCell>

                            <BentoCell
                                icon={Building2}
                                title="Built for teams and clients"
                                className="md:col-span-3"
                            >
                                <p className={cellCopy}>
                                    Group sites under clients, invite your team
                                    with owner and member roles, and keep every
                                    site scoped to the right organization.
                                </p>
                            </BentoCell>

                            <BentoCell
                                icon={Zap}
                                title="Usage you can see"
                                className="md:col-span-3"
                            >
                                <p className={cellCopy}>
                                    Analyses, summaries and crawls draw from a
                                    transparent credit balance — track exactly
                                    what you use, never surprised at month end.
                                </p>
                                <div className="mt-auto flex items-center gap-2 text-xs">
                                    <span className="tabular-nums text-muted-foreground">
                                        486 / 500 credits
                                    </span>
                                    <span className="h-1 w-24 overflow-hidden rounded-full bg-muted">
                                        <span className="block h-full w-[97%] rounded-full bg-emerald-500" />
                                    </span>
                                </div>
                            </BentoCell>
                        </section>
                    </main>

                    <footer className="mx-auto flex w-full max-w-6xl items-center justify-between border-t border-border px-6 py-6 text-xs text-muted-foreground">
                        <span>Satsuma</span>
                        <nav className="flex items-center gap-4">
                            <a
                                href="/privacy.html"
                                className="hover:text-foreground"
                            >
                                Privacy policy
                            </a>
                            <a
                                href="/cookies.html"
                                className="hover:text-foreground"
                            >
                                Cookie policy
                            </a>
                        </nav>
                    </footer>
                </div>
            </div>
        </>
    );
}
