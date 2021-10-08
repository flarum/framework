/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation based on the provided locale otherwise will default to the users locale.
 *
 * @example
 * formatNumber(1234, 'en-US');
 * // 1,234
 */
export default function formatNumber(number: number, locale?: string): string {
  if (typeof locale === 'undefined') {
    const locale: string = app.data.locale;
  }

  return new Intl
    .NumberFormat(locale)
    .format(number);
}
