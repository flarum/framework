import type Mithril from 'mithril';
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
export default function highlight(string: string, phrase?: string | RegExp, length?: number, safe?: boolean): Mithril.Vnode<any, any> | string;
