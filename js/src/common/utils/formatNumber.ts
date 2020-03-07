/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation.
 *
 * @param number Number to format
 * @param options Maximum significant digits or formatting options object
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 */
export default function formatNumber(number: number, options: number | Intl.NumberFormatOptions = {}): string {
    const config = typeof options === 'number' ? { maximumSignificantDigits: options } : options;

    return number.toLocaleString(app.translator.locale, config);
}
