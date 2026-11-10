export interface FontAwesomeIcon {
    /** The icon's current name, without the `fa-` prefix. */
    name: string;
    /** The glyph the icon draws. */
    char: string;
    /** Its name and any older aliases, space-separated, for searching. */
    search: string;
}
export interface FontAwesomeIconSets {
    classic: FontAwesomeIcon[];
    brands: FontAwesomeIcon[];
}
export interface FontAwesomeStyle {
    /** The class that selects the style, such as `fas` or `fasl`. */
    prefix: string;
    /** The family: `classic`, `duotone`, `sharp`, `sharp-duotone`, `brands`… */
    family: string;
    /** The font weight: 900 solid, 400 regular, 300 light, 100 thin. */
    weight: number;
    /** The font the family draws with, such as "Font Awesome 7 Pro". */
    font: string;
}
/**
 * Fetch the stylesheets under the forum's asset URL that the page can't read,
 * so their icons can be listed. A stylesheet served from another domain, such
 * as a bucket or CDN, is loaded without CORS, and the browser keeps its rules
 * from scripts even where the host would allow them. Fetched again with CORS,
 * those rules can be read wherever the host sends the header for it.
 *
 * Returns `null` when there is nothing to fetch, so the icons can be read
 * straight away.
 */
export declare function fetchUnreadableStylesheets(assetsBaseUrl: string | null | undefined): Promise<void> | null;
/**
 * The Font Awesome icons this forum can draw, read from the stylesheets it has
 * already loaded. There is no icon list to download, and the result always
 * matches the Font Awesome version in use, including a Kit's.
 *
 * Every icon is a rule like `.fa-house{--fa:"\f015"}`. Brand icons follow the
 * brands `@font-face`, which is what tells a brand from a classic icon.
 *
 * Stylesheets served from another domain can only be read once fetched again
 * with CORS: see `fetchUnreadableStylesheets()`.
 */
export default function fontAwesomeIcons(): FontAwesomeIconSets;
/**
 * The styles this forum can draw icons in: each family (classic, duotone,
 * sharp, brands…) at each weight (solid, regular, light, thin) that a loaded
 * font covers. The bundled Free fonts give classic Solid and Regular, and
 * brands; a Pro Kit adds the rest.
 */
export declare function fontAwesomeStyles(): FontAwesomeStyle[];
/**
 * The icons a style draws. Every icon has a Solid style, and a Pro font draws
 * every icon in every style, but only a few hundred free icons have a Regular
 * style. So the other weights of a Free font are measured, which takes a
 * moment: that answer comes as a promise.
 */
export declare function fontAwesomeIconsFor(style: FontAwesomeStyle): FontAwesomeIcon[] | Promise<FontAwesomeIcon[]>;
