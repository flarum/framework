/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
  constructor() {
    // Set up an event handler so that whenever the content area is tapped,
    // the drawer will close.
    $('#content').click((e) => {
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
    return $('#app').hasClass('drawerOpen');
  }

  /**
   * Hide the drawer.
   *
   * @public
   */
  hide() {
    const $app = $('#app');

    // If the drawer isn't open, stop execution
    if (!$app.hasClass('drawerOpen')) return;

    const $drawer = $('#drawer');

    // Used to prevent `visibility: hidden` from breaking the exit animation
    $drawer.one('transitionend', () => $drawer.css('visibility', ''));
    $drawer.css('visibility', 'visible');

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

    this.$backdrop = $('<div/>')
      .addClass('drawer-backdrop fade')
      .appendTo('body')
      .click(() => this.hide());

    setTimeout(() => this.$backdrop.addClass('in'));
  }
}
