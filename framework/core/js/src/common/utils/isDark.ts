/**
 * The `isDark` utility converts a hex color to rgb, and then calcul a YIQ
 * value in order to get the appropriate brightness value (is it dark or is it
 * light?) See https://www.w3.org/TR/AERT/#color-contrast for references. A YIQ
 * value >= 128 is a light color.
 */

export default function isDark(hexcolor: String) {
  let hexnumbers = hexcolor.replace('#', '');

  if (hexnumbers.length == 3) {
    hexnumbers += hexnumbers;
  }

  const r = parseInt(hexnumbers.substr(0, 2), 16);
  const g = parseInt(hexnumbers.substr(2, 2), 16);
  const b = parseInt(hexnumbers.substr(4, 2), 16);
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;

  return yiq >= 128 ? false : true;
}
