/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {String}
 */
export default function extractText(vdom) {
  let text = '';

  if (vdom instanceof Array) {
    text += vdom.map(element => extractText(element)).join('');
  } else if (typeof vdom === 'object') {
    text += extractText(vdom.children);
  } else {
    text += vdom;
  }

  return text;
}
