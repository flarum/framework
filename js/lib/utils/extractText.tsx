/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {String}
 */
export default function extractText(vdom) {
  if (Array.isArray(vdom)) {
    return vdom.map(extractText).join('');
  } else if (typeof vdom === 'object') {
    return extractText(vdom.children);
  } else {
    return vdom;
  }
}
