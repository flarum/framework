/**
 * The `yiqValue` utility converts a hex color to rgb, and then calcul a yiq
 * contrast value.
 */

export default function yiqValue(hexcolor: String) {

  var hexnumbers = hexcolor.replace("#", "");

  if (hexnumbers.length == 3) {
    hexnumbers += hexnumbers;
  }

  const r = parseInt(hexnumbers.substr(0,2),16);
  const g = parseInt(hexnumbers.substr(2,2),16);
  const b = parseInt(hexnumbers.substr(4,2),16);
  const contrast = ((r*299)+(g*587)+(b*114))/1000;

  return contrast;
}
