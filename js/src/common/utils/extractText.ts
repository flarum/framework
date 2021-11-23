import type Mithril from 'mithril';

/**
 * Extract the text nodes from a virtual element.
 */
export default function extractText(vdom: Mithril.Children): string {
  if (vdom instanceof Array) {
    return vdom.map((element) => extractText(element)).join('');
  } else if (typeof vdom === 'object' && vdom !== null) {
    return vdom.children ? extractText(vdom.children) : String(vdom.text);
  } else {
    return String(vdom);
  }
}
