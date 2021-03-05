/**
 * @see https://stackoverflow.com/a/31732310
 */
export default function isSafariMobile(): boolean {
  return (
    'ontouchstart' in window &&
    navigator.vendor &&
    navigator.vendor.indexOf('Apple') > -1 &&
    navigator.userAgent &&
    navigator.userAgent.indexOf('CriOS') == -1 &&
    navigator.userAgent.indexOf('FxiOS') == -1
  );
}
