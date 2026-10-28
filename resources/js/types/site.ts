export type Client = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    notes: string | null;
};

export type SitePlugin = {
    id: number;
    site_id: number;
    name: string;
    slug: string;
    version: string | null;
    is_active: boolean;
    update_available: boolean;
    new_version: string | null;
};

export type SiteTheme = {
    id: number;
    site_id: number;
    name: string;
    slug: string;
    version: string | null;
    is_active: boolean;
    update_available: boolean;
    new_version: string | null;
};

export type Site = {
    id: number;
    client_id: number | null;
    client?: Client | null;
    name: string;
    url: string;
    connection_status: 'online' | 'offline';
    scan_status: 'idle' | 'queued' | 'running';
    wp_version: string | null;
    php_version: string | null;
    last_ping_at: string | null;
    last_scan_at: string | null;
    whois?: {
        domain?: string;
        registrar?: string | null;
        registered_at?: string | null;
        expires_at?: string | null;
        nameservers?: string[];
    } | null;
    whois_checked_at?: string | null;
    plugins?: SitePlugin[];
    themes?: SiteTheme[];
};

export type SiteAnalyses = {
    pagespeed_mobile?: SiteAnalysis | null;
    pagespeed_desktop?: SiteAnalysis | null;
};

export type PageSpeedPayload = {
    strategy: 'mobile' | 'desktop';
    scores: Record<string, number | null>;
    metrics: { id: string; label: string; value: string; score: number | null }[];
    opportunities: { title: string; savings: string }[];
};

export type AnalysisSummary = {
    headline: string;
    points: string[];
};

export type SiteAnalysis = {
    id: number;
    type: string;
    score: number | null;
    data: PageSpeedPayload | null;
    summary?: AnalysisSummary | null;
    created_at: string;
};

export type DashboardStats = {
    totalSites: number;
    onlineSites: number;
    offlineSites: number;
    pendingUpdates: number;
};
