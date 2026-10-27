import type { SVGAttributes } from 'react';

export function WpLogo(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" {...props}>
            <circle
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="1.6"
            />
            <path
                d="M7 8.5l2 7 3-5 3 5 2-7"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

export function PhpLogo(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" {...props}>
            <ellipse
                cx="12"
                cy="12"
                rx="11"
                ry="7"
                stroke="currentColor"
                strokeWidth="1.4"
            />
            <text
                x="12"
                y="14.2"
                textAnchor="middle"
                fontSize="6"
                fontStyle="italic"
                fontFamily="ui-sans-serif, system-ui, sans-serif"
                fill="currentColor"
            >
                php
            </text>
        </svg>
    );
}
