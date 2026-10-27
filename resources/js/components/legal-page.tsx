import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

export function LegalPage({
    title,
    updated,
    children,
}: {
    title: string;
    updated: string;
    children: React.ReactNode;
}) {
    return (
        <>
            <Head title={`${title} — Shoal`} />
            <div className="dark relative min-h-screen bg-background text-foreground">
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{
                        background:
                            'radial-gradient(ellipse 60rem 30rem at 50% -14rem, color-mix(in oklab, var(--primary) 14%, transparent), transparent)',
                    }}
                />

                <div className="relative mx-auto flex w-full max-w-3xl flex-col px-6 py-8">
                    <header className="flex items-center justify-between">
                        <Link href={home()} className="flex items-center gap-2.5">
                            <AppLogoIcon className="size-6 text-primary" />
                            <span className="text-sm font-semibold tracking-tight">
                                Shoal
                            </span>
                        </Link>
                        <Link
                            href={home()}
                            className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="size-4" />
                            Back
                        </Link>
                    </header>

                    <main className="mt-16">
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                            {title}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Last updated: {updated}
                        </p>
                        <div className="mt-10 space-y-8 text-sm leading-relaxed text-muted-foreground [&_h2]:mb-3 [&_h2]:text-lg [&_h2]:font-medium [&_h2]:text-foreground">
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
