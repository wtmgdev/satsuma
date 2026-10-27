import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type NotificationItem = {
    id: string;
    type: string;
    data: {
        title: string;
        body: string;
        url?: string;
        site_id?: number;
    };
    read_at: string | null;
    created_at: string;
};

export function NotificationBell() {
    const { unreadNotifications } = usePage().props as { unreadNotifications?: number };
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState<number>(unreadNotifications ?? 0);

    useEffect(() => {
        setUnread(unreadNotifications ?? 0);
    }, [unreadNotifications]);

    useEffect(() => {
        if (!open) {
            return;
        }

        fetch('/notifications', { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                setItems(data.notifications ?? []);
                setUnread(data.unread ?? 0);
            })
            .catch(() => {});
    }, [open]);

    const markAllRead = () => {
        router.post('/notifications/read', {}, {
            preserveScroll: true,
            onSuccess: () => setUnread(0),
        });
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button size="icon" variant="ghost" className="relative" title="Notifications">
                    <Bell />
                    {unread > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-semibold text-white">
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <div className="flex items-center justify-between px-3 py-2">
                    <span className="text-sm font-medium">Notifications</span>
                    {unread > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            Mark all read
                        </button>
                    )}
                </div>
                <div className="max-h-80 overflow-y-auto">
                    {items.length === 0 ? (
                        <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                            Nothing here yet.
                        </p>
                    ) : (
                        items.map((item) => (
                            <a
                                key={item.id}
                                href={item.data.url ?? '#'}
                                className={`block px-3 py-2.5 text-sm hover:bg-accent ${item.read_at ? 'opacity-60' : ''}`}
                            >
                                <div className="font-medium">{item.data.title}</div>
                                <div className="text-xs text-muted-foreground">
                                    {item.data.body}
                                </div>
                            </a>
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
