/**
 * The `anchorScroll` utility saves the scroll position relative to an element,
 * and then restores it after a callback has been run.
 *
 * This is useful if a redraw will change the page's content above the viewport.
 * Normally doing this will result in the content in the viewport being pushed
 * down or pulled up. By wrapping the redraw with this utility, the scroll
 * position can be anchor to an element that is in or below the viewport, so
 * the content in the viewport will stay the same.
 *
 * @param element The element to anchor the scroll position to.
 * @param callback The callback to run that will change page content.
 */
export default function anchorScroll(element: HTMLElement, callback: Function) {
    const $window = $(window);
    const $el = $(element);

    if (!element || !$el.length) {
        return callback();
    }

    const relativeScroll = $el.offset().top - $window.scrollTop();

    callback();

    $window.scrollTop($el.offset().top - relativeScroll);
}
