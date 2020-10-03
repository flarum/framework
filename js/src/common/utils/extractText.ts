/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {string}
 */
export default function extractText(vdom: IVirtualElement): string {
  if (vdom instanceof Array) {
    return vdom.map((element) => extractText(element)).join('');
  } else if (typeof vdom === 'object' && vdom !== null) {
    // @ts-ignore
    return vdom.children ? extractText(vdom.children) : vdom.text;
  } else {
    return vdom;
  }
}

export interface IVirtualElement extends HTMLElement {
  _virtual: {
    parent?: IVirtualElement;
    children: IVirtualElement[];
  };
}
