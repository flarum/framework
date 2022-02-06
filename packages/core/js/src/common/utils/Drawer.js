import { createFocusTrap } from './focusTrap';

/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
  /**
   * @type {import('./focusTrap').FocusTrap}
   */
  focusTrap;

  /**
   * @type {HTMLDivElement}
   */
  appElement;

  constructor() {
    // Set up an event handler so that whenever the content area is tapped,
    // the drawer will close.
    document.getElementById('content').addEventListener('click', (e) => {
      if (this.isOpen()) {
        e.preventDefault();
        this.hide();
      }
    });

    this.appElement = document.getElementById('app');
    // Despite the `focus-trap` documentation, both `clickOutsideDeactivates`
    // and `allowOutsideClick` are necessary so that inputs in modals triggered
    // from the drawer's nav components can be interacted with.
    this.focusTrap = createFocusTrap('#drawer', { allowOutsideClick: true, clickOutsideDeactivates: true });
    this.drawerAvailableMediaQuery = window.matchMedia(
      `(max-width: ${getComputedStyle(document.documentElement).getPropertyValue('--screen-phone-max')})`
    );
  }

  /**
   * Handler for the `resize` event on `window`.
   *
   * This is used to close the drawer when the viewport is widened past the `phone` size.
   * At this point, the drawer turns into the standard header that we see on desktop, but
   * the drawer is still registered as 'open' internally.
   *
   * This causes issues with the focus trap, resulting in focus becoming trapped within
   * the header on desktop viewports.
   *
   * @internal
   */
  resizeHandler = ((e) => {
    if (!e.matches && this.isOpen()) {
      // Drawer is open but we've made window bigger, so hide it.
      this.hide();
    }
  }).bind(this);

  /**
   * @internal
   * @type {MediaQueryList}
   */
  drawerAvailableMediaQuery;

  /**
   * Check whether or not the drawer is currently open.
   *
   * @return {boolean}
   */
  isOpen() {
    return this.appElement.classList.contains('drawerOpen');
  }

  /**
   * Hide the drawer.
   */
  hide() {
    /**
     * As part of hiding the drawer, this function also ensures that the drawer
     * correctly animates out, while ensuring it is not part of the navigation
     * tree while off-screen.
     *
     * More info: https://github.com/flarum/core/pull/2666#discussion_r595381014
     */

    this.focusTrap.deactivate();
    this.drawerAvailableMediaQuery.removeListener(this.resizeHandler);

    if (!this.isOpen()) return;

    const $drawer = $('#drawer');

    // Used to prevent `visibility: hidden` from breaking the exit animation
    $drawer.css('visibility', 'visible').one('transitionend', () => $drawer.css('visibility', ''));

    this.appElement.classList.remove('drawerOpen');

    this.$backdrop?.remove?.();
  }

  /**
   * Show the drawer.
   */
  show() {
    this.appElement.classList.add('drawerOpen');

    this.drawerAvailableMediaQuery.addListener(this.resizeHandler);

    this.$backdrop = $('<div/>').addClass('drawer-backdrop fade').appendTo('body').on('click', this.hide.bind(this));

    requestAnimationFrame(() => {
      this.$backdrop.addClass('in');

      this.focusTrap.activate();
    });
  }
}
