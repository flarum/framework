import type Mithril from 'mithril';
/**
 * The `highlight` helper searches for a word phrase in a string, and wraps
 * matches with the <mark> tag.
 *
 * @param string The string to highlight.
 * @param phrase The word or words to highlight.
 * @param [length] The number of characters to truncate the string to.
 *     The string will be truncated surrounding the first match.
 * @param safe Whether the content is safe to render as HTML or
 *    should be escaped (HTML entities encoded).
 */
export default function highlight(string: string, phrase?: string | RegExp, length?: number, safe?: boolean): Mithril.Vnode<any, any> | string;
