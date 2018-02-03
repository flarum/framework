/**
 * Truncate a string to the given length, appending ellipses if necessary.
 *
 * @param {String} string
 * @param {Number} length
 * @param {Number} [start=0]
 * @return {String}
 */
export function truncate(string, length, start = 0) {
  return (start > 0 ? '...' : '') +
    string.substring(start, start + length) +
    (string.length > start + length ? '...' : '');
}

/**
 * Create a slug out of the given string.
 *
 * nonsafe URL characters are converted to hyphens.
 *
 * @param {String} string
 * @return {String}
 */
export function slug(string) {
  // Regex for finding the nonsafe URL characters (many need escaping): & +$,:;=?@"#{}|^~[`%!']./()*\
  var nonsafeChars = /[& +$,:;=?@"#{}|^~[`%!'\]\.\/\(\)\*\\]/g;

  // Note: we trim hyphens after truncating because truncating can cause dangling hyphens.
  // Example string:                      // " ⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
  string = string.trim()                  // "⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
    .replace(/\'/gi, '')                  // "⚡⚡ Dont forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
    .replace(nonsafeChars, '-')           // "⚡⚡-Dont-forget--URL-fragments-should-be-i18n-friendly--hyphenated--short--and-clean-"
    .replace(/-{2,}/g, '-')               // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-short-and-clean-"
    .substring(0, 64)                     // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-"
    .replace(/^-+|-+$/gm, '')             // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated"
    .toLowerCase();                       // "⚡⚡-dont-forget-url-fragments-should-be-i18n-friendly-hyphenated"

  return $string || '-';
}

/**
 * Strip HTML tags and quotes out of the given string, replacing them with
 * meaningful punctuation.
 *
 * @param {String} string
 * @return {String}
 */
export function getPlainContent(string) {
  const html = string
    .replace(/(<\/p>|<br>)/g, '$1 &nbsp;')
    .replace(/<img\b[^>]*>/ig, ' ');

  const dom = $('<div/>').html(html);

  dom.find(getPlainContent.removeSelectors.join(',')).remove();

  return dom.text().replace(/\s+/g, ' ').trim();
}

/**
 * An array of DOM selectors to remove when getting plain content.
 *
 * @type {Array}
 */
getPlainContent.removeSelectors = ['blockquote', 'script'];

/**
 * Make a string's first character uppercase.
 *
 * @param {String} string
 * @return {String}
 */
export function ucfirst(string) {
  return string.substr(0, 1).toUpperCase() + string.substr(1);
}
