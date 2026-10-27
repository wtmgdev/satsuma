import { Badge } from '@/components/ui/badge';
import { AlertTriangle } from 'lucide-react';

type PhpInfo = {
    installed: string | null;
    latest_patch: string | null;
    eol: boolean;
    eol_date: string | null;
};

type WpInfo = {
    installed: string | null;
    latest: string | null;
    update_available: boolean;
};

export function PhpVersionBadge({ php }: { php: PhpInfo }) {
    if (!php.installed) {
        return <span className="text-muted-foreground">—</span>;
    }

    if (php.eol) {
        return (
            <Badge variant="destructive">
                <AlertTriangle />
                {php.installed} · EOL
            </Badge>
        );
    }

    if (php.latest_patch && versionCompare(php.installed, php.latest_patch) < 0) {
        return (
            <Badge variant="warning">
                {php.installed} → {php.latest_patch}
            </Badge>
        );
    }

    return <Badge variant="outline">{php.installed}</Badge>;
}

export function WpVersionBadge({ wp }: { wp: WpInfo }) {
    if (!wp.installed) {
        return <span className="text-muted-foreground">—</span>;
    }

    if (wp.update_available && wp.latest) {
        return (
            <Badge variant="warning">
                {wp.installed} → {wp.latest}
            </Badge>
        );
    }

    return <Badge variant="outline">{wp.installed}</Badge>;
}

export type { PhpInfo, WpInfo };

function versionCompare(a: string, b: string): number {
    return a.localeCompare(b, 'en', { numeric: true });
}
