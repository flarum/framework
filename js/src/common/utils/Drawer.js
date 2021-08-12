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

  constructor() {
    // Set up an event handler so that whenever the content area is tapped,
    // the drawer will close.
    $('#content').on('click', (e) => {
      if (this.isOpen()) {
        e.preventDefault();
        this.hide();
      }
    });

    this.focusTrap = createFocusTrap('#drawer', { allowOutsideClick: true });
  }

  /**
   * Check whether or not the drawer is currently open.
   *
   * @return {Boolean}
   * @public
   */
  isOpen() {
    return $('#app').hasClass('drawerOpen');
  }

  /**
   * Hide the drawer.
   *
   * @public
   */
  hide() {
    /**
     * As part of hiding the drawer, this function also ensures that the drawer
     * correctly animates out, while ensuring it is not part of the navigation
     * tree while off-screen.
     *
     * More info: https://github.com/flarum/core/pull/2666#discussion_r595381014
     */

    const $app = $('#app');

    this.focusTrap.deactivate();

    if (!this.isOpen()) return;

    const $drawer = $('#drawer');

    // Used to prevent `visibility: hidden` from breaking the exit animation
    $drawer.css('visibility', 'visible').one('transitionend', () => $drawer.css('visibility', ''));

    $app.removeClass('drawerOpen');

    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * Show the drawer.
   *
   * @public
   */
  show() {
    $('#app').addClass('drawerOpen');

    this.$backdrop = $('<div/>').addClass('drawer-backdrop fade').appendTo('body').on('click', this.hide.bind(this));

    requestAnimationFrame(() => {
      this.$backdrop.addClass('in');

      this.focusTrap.activate();
    });
  }
}
