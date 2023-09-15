/**
 * The `isDark` utility converts a hex color to rgb, and then calculates a YIQ
 * value in order to get the appropriate brightness value. See
 * https://www.w3.org/TR/AERT/#color-contrast for references.
 *
 * A YIQ value >= 128 corresponds to a light color according to the W3C
 * standards, but we use a custom threshold for each light and dark modes
 * to preserve design consistency.
 */
export default function isDark(hexcolor: string | null): boolean;
