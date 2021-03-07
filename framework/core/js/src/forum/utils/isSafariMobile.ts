/**
 * @see https://stackoverflow.com/a/31732310
 */
export default function isSafariMobile(): boolean {
  return (
    'ontouchstart' in window &&
    navigator.vendor &&
    navigator.vendor.includes('Apple') &&
    navigator.userAgent &&
    !navigator.userAgent.includes('CriOS') &&
    !navigator.userAgent.includes('FxiOS')
  );
}
