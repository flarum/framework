import * as Mithril from 'mithril';
import { truncate } from '../utils/string';

/**
 * The `highlight` helper searches for a word phrase in a string, and wraps
 * matches with the <mark> tag.
 *
 * @param string The string to highlight.
 * @param phrase The word or words to highlight.
 * @param [length] The number of characters to truncate the string to.
 *     The string will be truncated surrounding the first match.
 */
export default function highlight(string: string, phrase: string | RegExp, length?: number): Mithril.Vnode<any, any> | string {
  if (!phrase && !length) return string;

  // Convert the word phrase into a global regular expression (if it isn't
  // already) so we can search the string for matched.
  const regexp = phrase instanceof RegExp ? phrase : new RegExp(phrase, 'gi');

  let highlighted = string;
  let start = 0;

  // If a length was given, the truncate the string surrounding the first match.
  if (length) {
    if (phrase) start = Math.max(0, string.search(regexp) - length / 2);

    highlighted = truncate(highlighted, length, start);
  }

  // Convert the string into HTML entities, then highlight all matches with
  // <mark> tags. Then we will return the result as a trusted HTML string.
  highlighted = $('<div/>').text(highlighted).html();

  if (phrase) highlighted = highlighted.replace(regexp, '<mark>$&</mark>');

  return m.trust(highlighted);
}
