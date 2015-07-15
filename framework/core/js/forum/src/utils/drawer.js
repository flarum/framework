/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
  constructor() {
    // Set up an event handler so that whenever the content area is tapped,
    // the drawer will close.
    $('.global-content').click(e => {
      if (this.isOpen()) {
        e.preventDefault();
        this.hide();
      }
    });
  }

  /**
   * Check whether or not the drawer is currently open.
   *
   * @return {Boolean}
   * @public
   */
  isOpen() {
    return $('body').hasClass('drawer-open');
  }

  /**
   * Hide the drawer.
   *
   * @public
   */
  hide() {
    $('body').removeClass('drawer-open');
  }

  /**
   * Show the drawer.
   *
   * @public
   */
  show() {
    $('body').addClass('drawer-open');
  }

  /**
   * Toggle the drawer.
   *
   * @public
   */
  toggle() {
    $('body').toggleClass('drawer-open');
  }
}
