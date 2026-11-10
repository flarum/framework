import type Mithril from 'mithril';
import { truncate } from '../utils/string';
import highlightHtml, { matchRanges } from '../utils/highlightHtml';

/**
 * The `highlight` helper searches for a word phrase in a string, and wraps
 * matches with the <mark> tag.
 *
 * @param string The string to highlight.
 * @param phrase The word or words to highlight.
 * @param [length] The number of characters to truncate the string to.
 *     The string will be truncated surrounding the first match.
 * @param safe Whether the string is HTML to be rendered as-is, or plain text
 *    to be escaped (HTML entities encoded). In HTML, only text is highlighted:
 *    tags and attributes are left untouched.
 */
export default function highlight(string: string, phrase?: string | RegExp, length?: number, safe = false): Mithril.Vnode<any, any> | string {
  if (!phrase && !length) return string;

  // Convert the word phrase into a global regular expression (if it isn't
  // already) so we can search the string for matched.
  const regexp = phrase instanceof RegExp ? phrase : new RegExp(phrase ?? '', 'gi');

  let highlighted = string;
  let start = 0;

  // If a length was given, the truncate the string surrounding the first match.
  if (length) {
    if (phrase) start = Math.max(0, string.search(regexp) - length / 2);

    highlighted = truncate(highlighted, length, start);
  }

  if (safe) {
    return m.trust(phrase ? highlightHtml(highlighted, regexp) : highlighted);
  }

  // Escape the text around and inside each match separately, so the
  // expression never matches inside the HTML entities that escaping adds.
  let html = '';
  let lastIndex = 0;

  for (const [start, end] of phrase ? matchRanges(highlighted, regexp) : []) {
    html += escapeHtml(highlighted.slice(lastIndex, start)) + '<mark>' + escapeHtml(highlighted.slice(start, end)) + '</mark>';
    lastIndex = end;
  }

  highlighted = html + escapeHtml(highlighted.slice(lastIndex));

  return m.trust(highlighted);
}

function escapeHtml(text: string): string {
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
