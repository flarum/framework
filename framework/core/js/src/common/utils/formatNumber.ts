/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation.
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 */
export default function formatNumber(number: number): string {
  return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
