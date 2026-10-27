import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    Globe,
    RefreshCw,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login } from '@/routes';

const features = [
    {
        icon: Globe,
        title: 'Site inventory scans',
        description:
            'Connect a site with a one-line agent. Scans pull WordPress and PHP versions, plugins and themes — automatically.',
    },
    {
        icon: RefreshCw,
        title: 'Update tracking',
        description:
            'See every outdated plugin, theme and core version at a glance — including premium plugins and PHP EOL warnings.',
    },
    {
        icon: Building2,
        title: 'Organizations',
        description:
            'Group sites under clients, invite your team with roles, and keep every site scoped to the right organization.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Shoal — WordPress management" />
            <div className="dark relative min-h-screen bg-background text-foreground">
                <div className="pointer-events-none absolute inset-x-0 top-0 flex justify-center overflow-hidden">
                    <div className="mt-[-20rem] h-[36rem] w-[64rem] rounded-full bg-primary/10 blur-[120px]" />
                </div>

                <div className="relative flex min-h-screen flex-col">
                    <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
                        <div className="flex items-center gap-2.5">
                            <AppLogoIcon className="size-7 text-primary" />
                            <span className="text-sm font-semibold tracking-tight">
                                Shoal
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
                                <>
                                    <Button size="sm" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </>
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
                                Shoal keeps track of versions, plugins and
                                updates across every site you manage — so
                                nothing drifts out of date unnoticed.
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

                        {false && (
                            <section className="grid gap-4 pb-24 md:grid-cols-3">
                                {features.map((feature) => (
                                    <Card key={feature.title} className="gap-3">
                                        <CardHeader>
                                            <span className="flex size-9 items-center justify-center rounded-md bg-muted">
                                                <feature.icon className="text-muted-foreground" />
                                            </span>
                                            <CardTitle className="text-base">
                                                {feature.title}
                                            </CardTitle>
                                            <CardDescription>
                                                {feature.description}
                                            </CardDescription>
                                        </CardHeader>
                                    </Card>
                                ))}
                            </section>
                        )}
                    </main>

                    <footer className="mx-auto flex w-full max-w-6xl items-center justify-between border-t border-border px-6 py-6 text-xs text-muted-foreground">
                        <span>Shoal — self-hosted WordPress management</span>
                        <Link href={login()} className="hover:text-foreground">
                            Log in
                        </Link>
                    </footer>
                </div>
            </div>
        </>
    );
}
