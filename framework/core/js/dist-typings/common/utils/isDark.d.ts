/**
 * The `isDark` utility tells whether a colour needs light text on it: it
 * returns true when `--text-on-dark` (white by default) has a higher WCAG
 * contrast ratio against the colour than `--text-on-light` does. This is the
 * same choice CSS `contrast-color()` makes, against the theme's own text
 * colours rather than plain white and black. A translucent colour is taken
 * as it shows over the page background (`--body-bg`).
 *
 * A theme that sets `--yiq-threshold` gets the older YIQ brightness cut-off
 * instead (see https://www.w3.org/TR/AERT/#color-contrast). That override is
 * deprecated and will be removed in Flarum 3.0.
 */
export default function isDark(hexcolor: string | null): boolean;
