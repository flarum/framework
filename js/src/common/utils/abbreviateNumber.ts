/**
 * The `abbreviateNumber` utility converts a number to a shorter localized form.
 *
 * @example
 * abbreviateNumber(1234);
 * // "1.2K"
 */
export default function abbreviateNumber(number: number): string {
  // TODO: translation
  if (number >= 1000000) {
    return Math.floor(number / 1000000) + app.translator.trans('core.lib.number_suffix.mega_text');
  } else if (number >= 1000) {
    return (number / 1000).toFixed(1) + app.translator.trans('core.lib.number_suffix.kilo_text');
  } else {
    return number.toString();
  }
}
