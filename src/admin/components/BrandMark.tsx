/**
 * Brand mark — the circular orbital logo we use in the WP admin menu,
 * rendered inline so it can sit alongside the wordmark in the header
 * strip. Same SVG path as React_Admin::get_menu_icon() on the PHP
 * side; keep the two in sync if either changes.
 */

interface BrandMarkProps {
    size?: number;
}

export function BrandMark({ size = 24 }: BrandMarkProps): JSX.Element {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width={size}
            height={size}
            viewBox="0 0 322 322"
            fill="none"
            aria-hidden="true"
            focusable="false"
            className="orbitools-brand-mark"
        >
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M71.096 27.45A160.999 160.999 0 0 1 160.369.013 159.624 159.624 0 0 1 275.03 46.53a159.612 159.612 0 0 1 46.964 114.477 160.99 160.99 0 0 1-99.242 148.678A160.999 160.999 0 0 1 3.171 192.798 160.99 160.99 0 0 1 71.096 27.45Zm45.655 198.564a78.138 78.138 0 0 0 43.409 13.167 78.22 78.22 0 0 0 78.134-78.132 78.133 78.133 0 1 0-121.543 64.965Zm149.52-151.706c0 12.54-10.166 22.705-22.706 22.705-12.539 0-22.705-10.166-22.705-22.705 0-12.54 10.166-22.705 22.705-22.705 12.54 0 22.706 10.165 22.706 22.705Z"
                clipRule="evenodd"
            />
        </svg>
    );
}
