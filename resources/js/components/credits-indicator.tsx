import { usePage } from '@inertiajs/react';
import { Zap } from 'lucide-react';

type Credits = {
    balance: number;
    purchased: number;
    allowance: number;
    unlimited: boolean;
    resetsAt: string | null;
};

export function CreditsIndicator() {
    const { credits } = usePage().props as { credits?: Credits | null };

    if (!credits) {
        return null;
    }

    if (credits.unlimited) {
        return (
            <div className="flex items-center gap-2 px-2 py-1.5 text-xs text-muted-foreground">
                <Zap className="size-3.5 text-orange-400" />
                <span>Unlimited credits</span>
            </div>
        );
    }

    const ratio = credits.allowance > 0 ? credits.balance / credits.allowance : 0;
    const color = ratio > 0.2 ? 'bg-emerald-500' : ratio > 0 ? 'bg-amber-500' : 'bg-red-500';
    const resets = credits.resetsAt
        ? new Date(credits.resetsAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
        : null;
    const title = [
        resets ? `Resets ${resets}` : null,
        credits.purchased > 0 ? `${credits.purchased} purchased` : null,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <div
            className="flex flex-col gap-1.5 px-2 py-1.5"
            title={title || undefined}
        >
            <span className="flex items-center gap-2 text-xs text-muted-foreground">
                <Zap className="size-3.5 text-orange-400" />
                <span className="tabular-nums">
                    {credits.balance + credits.purchased} credits left
                </span>
            </span>
            <div className="h-1 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className={`h-full rounded-full ${color}`}
                    style={{ width: `${Math.max(Math.min(ratio * 100, 100), 2)}%` }}
                />
            </div>
        </div>
    );
}
