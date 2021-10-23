/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation based on the provided locale otherwise will default to the users locale.
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 */
export default function formatNumber(number: number, locale?: string): string;
