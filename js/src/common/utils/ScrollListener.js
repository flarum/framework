const later =
  window.requestAnimationFrame ||
  window.webkitRequestAnimationFrame ||
  window.mozRequestAnimationFrame ||
  window.msRequestAnimationFrame ||
  window.oRequestAnimationFrame ||
  ((callback) => window.setTimeout(callback, 1000 / 60));

/**
 * The `ScrollListener` class sets up a listener that handles window scroll
 * events.
 */
export default class ScrollListener {
  /**
   * @param {Function} callback The callback to run when the scroll position
   *     changes.
   * @public
   */
  constructor(callback) {
    this.callback = callback;
    this.ticking = false;
  }

  /**
   * On each animation frame, as long as the listener is active, run the
   * `update` method.
   *
   * @protected
   */
  loop() {
    // THROTTLE: If the callback is still running (or hasn't yet run), we ignore
    // further scroll events.
    if (this.ticking) return;

    // Schedule the callback to be executed soon (TM), and stop throttling once
    // the callback is done.
    later(() => {
      this.update();
      this.ticking = false;
    });

    this.ticking = true;
  }

  /**
   * Run the callback, whether there was a scroll event or not.
   *
   * @public
   */
  update() {
    this.callback(window.pageYOffset);
  }

  /**
   * Start listening to and handling the window's scroll position.
   *
   * @public
   */
  start() {
    if (!this.active) {
      window.addEventListener('scroll', (this.active = this.loop.bind(this)));
    }
  }

  /**
   * Stop listening to and handling the window's scroll position.
   *
   * @public
   */
  stop() {
    window.removeEventListener('scroll', this.active);

    this.active = null;
  }
}
