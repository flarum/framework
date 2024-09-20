/**
 * The `slidable` utility adds touch gestures to an element so that it can be
 * slid away to reveal controls underneath, and then released to activate those
 * controls.
 *
 * It relies on the element having children with particular CSS classes.
 *
 * The function returns a record with a `reset` proeprty. This is a function
 * which reverts the slider to its original position. This should be called,
 * for example, when a controls dropdown is closed.
 *
 * @param {HTMLElement | SVGElement | Element} element
 * @return {{ reset : () => void }}
 */
export default function slidable(element: HTMLElement | SVGElement | Element): {
    reset: () => void;
};
