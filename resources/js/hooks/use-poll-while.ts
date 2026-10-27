import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

/**
 * Partially reload Inertia props on an interval while `active` is true.
 * Stops after `maxPolls` consecutive reloads without `active` turning false,
 * so permanently-offline states don't poll forever.
 */
export function usePollWhile(
    active: boolean,
    only: string[],
    intervalMs = 2500,
    maxPolls = 24,
): void {
    const pollsRef = useRef(0);
    const activeRef = useRef(active);

    activeRef.current = active;

    useEffect(() => {
        if (!active) {
            pollsRef.current = 0;

            return;
        }

        const timer = window.setInterval(() => {
            if (!activeRef.current || pollsRef.current >= maxPolls) {
                return;
            }

            pollsRef.current += 1;

            router.reload({ only });
        }, intervalMs);

        return () => window.clearInterval(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active, intervalMs, maxPolls]);
}
