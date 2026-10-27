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
    plugins?: SitePlugin[];
    themes?: SiteTheme[];
};

export type DashboardStats = {
    totalSites: number;
    onlineSites: number;
    offlineSites: number;
    pendingUpdates: number;
};
