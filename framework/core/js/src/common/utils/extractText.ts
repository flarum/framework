import type Mithril from 'mithril';

/**
 * Extract the text nodes from a virtual element.
 *
 * Absent children (`null`, `undefined` and booleans) render nothing in Mithril,
 * so they contribute no text. This lets conditional content such as
 * `['Tags', showExtra && extra]` be extracted safely.
 */
export default function extractText(vdom: Mithril.Children): string {
  if (vdom === null || vdom === undefined || typeof vdom === 'boolean') {
    return '';
  } else if (vdom instanceof Array) {
    return vdom.map((element) => extractText(element)).join('');
  } else if (typeof vdom === 'object') {
    return extractText(vdom.children);
  } else {
    return String(vdom);
  }
}
