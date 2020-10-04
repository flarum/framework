import * as mithril from 'mithril';

/**
 * Extract the text nodes from a virtual element.
 *
 * @param {mithril.VnodeDOM} vdom
 * @return {IExtractTextReturn}
 */
export default function extractText(vdom: mithril.VnodeDOM): IExtractTextReturn {
  if (vdom instanceof Array) {
    return vdom.map((element) => extractText(element)).join('');
  } else if (typeof vdom === 'object' && vdom !== null) {
    // @ts-ignore
    return vdom.children ? extractText(vdom.children) : vdom.text;
  } else {
    return vdom;
  }
}

export type IExtractTextReturn = string | number | boolean | undefined;
