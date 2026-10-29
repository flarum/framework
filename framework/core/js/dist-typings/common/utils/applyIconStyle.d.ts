/**
 * Rewrite the style classes of a FontAwesome icon class list to the given
 * style, e.g. `fas fa-user` -> `fa-duotone fa-light fa-user`.
 *
 * Brand icons are never rewritten: their glyphs only exist in the brands
 * family, so forcing a style would blank them. Everything that isn't a style
 * class — the icon name and any utility classes — passes through untouched.
 */
export default function applyIconStyle(name: string | null | undefined, style: string): string | null | undefined;
