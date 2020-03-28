/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {String}
 */
export default function extractText(vdom: any): string {
    if (vdom instanceof Array) {
        return vdom.map((element) => extractText(element)).join('');
    } else if (typeof vdom === 'object' && vdom !== null) {
        return vdom.text || extractText(vdom.children);
    } else {
        return vdom;
    }
}
