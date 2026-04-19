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
 * @param {string | HTMLElement | SVGElement | Element} element The element to anchor the scroll position to.
 * @param {() => void} callback The callback to run that will change page content.
 */
export default function anchorScroll(element, callback) {
  const $window = $(window);
  const relativeScroll = $(element).offset().top - $window.scrollTop();

  callback();

  // Use `window.scrollTo()` instead of jQuery's `.scrollTop()`. On iOS Safari,
  // programmatic writes to `scrollTop` during an in-flight momentum/inertial
  // scroll are silently ignored, leaving the viewport to continue animating
  // from the old absolute offset — which produces a visible jump when the
  // callback changes content above the viewport (e.g. PostStream prepending
  // older posts during `loadPrevious()`). `window.scrollTo(x, y)` goes through
  // a different WebKit code path that halts the in-flight momentum and applies
  // the new position. See flarum/framework#4587.
  window.scrollTo(window.scrollX, $(element).offset().top - relativeScroll);
}
