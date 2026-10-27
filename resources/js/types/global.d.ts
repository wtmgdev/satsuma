import type { Auth, Organization } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            organizations: Organization[];
            currentOrganization: Organization | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
