import type { SVGAttributes } from 'react';

/**
 * Jalwala brand mark — water droplet with jar cap and wave lines.
 * Uses currentColor so it adapts to light/dark contexts and gradient tiles.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M16 3.5c-.48 0-.92.19-1.24.5C10.1 8.42 8 13.2 8 17.75 8 22.75 11.58 26.5 16 26.5s8-3.75 8-8.75c0-4.55-2.1-9.33-6.76-13.75A1.72 1.72 0 0 0 16 3.5Z"
            />
            <path
                fill="currentColor"
                fillOpacity={0.22}
                d="M16 7.25c2.65 2.9 4.4 6.72 4.4 10.5 0 2.35-1.9 4.25-4.25 4.25S11.9 20.1 11.9 17.75c0-3.78 1.75-7.6 4.4-10.5Z"
            />
            <path
                fill="currentColor"
                d="M12.25 2.75h7.5a1.25 1.25 0 0 1 1.25 1.25V4.5a.75.75 0 0 1-.75.75h-8.5a.75.75 0 0 1-.75-.75V4c0-.69.56-1.25 1.25-1.25Z"
            />
            <path
                stroke="currentColor"
                strokeWidth={1.35}
                strokeLinecap="round"
                strokeOpacity={0.55}
                d="M10 18.25c2-1 4.1-1 6 0s4.05.45 6 0"
            />
            <path
                stroke="currentColor"
                strokeWidth={1.35}
                strokeLinecap="round"
                strokeOpacity={0.35}
                d="M10 21c2-1 4.1-1 6 0s4.05.45 6 0"
            />
        </svg>
    );
}
