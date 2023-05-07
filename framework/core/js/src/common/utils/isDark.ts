/**
 * The `isDark` utility converts a hex color to rgb, and then calculates a YIQ
 * value in order to get the appropriate brightness value. See
 * https://www.w3.org/TR/AERT/#color-contrast for references.
 *
 * A YIQ value >= 128 corresponds to a light color according to the W3C
 * standards, but we use a custom threshold for each light and dark modes
 * to preserve design consistency.
 */
export default function isDark(hexcolor: string | null): boolean {
  // return if hexcolor is undefined or shorter than 4 characters, shortest hex form is #333;
  // decided against regex hex color validation for performance considerations
  if (!hexcolor || hexcolor.length < 4) {
    return false;
  }

  let hexnumbers = hexcolor.replace('#', '');

  if (hexnumbers.length === 3) {
    hexnumbers += hexnumbers;
  }

  const r = parseInt(hexnumbers.slice(0, 2), 16);
  const g = parseInt(hexnumbers.slice(2, 4), 16);
  const b = parseInt(hexnumbers.slice(4, 6), 16);
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;

  const threshold = parseInt(getComputedStyle(document.body).getPropertyValue('--yiq-threshold').trim()) || 128;

  return yiq < threshold;
}
