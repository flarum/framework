/**
 * The `isDark` utility converts a hex color to rgb, and then calculates a YIQ
 * value in order to get the appropriate brightness value. See
 * https://www.w3.org/TR/AERT/#color-contrast for references.
 *
 * A YIQ value >= 128 corresponds to a light color according to the W3C
 * standards, but we use a custom threshold for each light and dark modes
 * to preserve design consistency.
 */
export default function isDark(hexcolor: string): boolean {
  let hexnumbers = hexcolor.replace('#', '');

  if (hexnumbers.length == 3) {
    hexnumbers += hexnumbers;
  }

  const r = parseInt(hexnumbers.substr(0, 2), 16);
  const g = parseInt(hexnumbers.substr(2, 2), 16);
  const b = parseInt(hexnumbers.substr(4, 2), 16);
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;

  const threshold = parseInt(window.getComputedStyle(document.body).getPropertyValue('--yiq-threshold'));

  return yiq < threshold;
}
