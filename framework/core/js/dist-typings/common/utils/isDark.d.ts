/**
 * The `isDark` utility converts a hex color to rgb, and then calcul a YIQ
 * value in order to get the appropriate brightness value (is it dark or is it
 * light?) See https://www.w3.org/TR/AERT/#color-contrast for references. A YIQ
 * value >= 128 is a light color.
 */
export default function isDark(hexcolor: String): boolean;
