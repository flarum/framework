/**
 * The `slidable` utility adds touch gestures to an element so that it can be
 * slid away to reveal controls underneath, and then released to activate those
 * controls.
 *
 * It relies on the element having children with particular CSS classes.
 * TODO: document
 *
 * @param {DOMElement} element
 * @return {Object}
 * @property {function} reset Revert the slider to its original position. This
 *     should be called, for example, when a controls dropdown is closed.
 */
export default function slidable(element: any): Object;
