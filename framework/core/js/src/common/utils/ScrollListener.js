/**
 * The `ScrollListener` class sets up a listener that handles element scroll
 * events.
 */
export default class ScrollListener {
  /**
   * @param {(top: number) => void} callback The callback to run when the scroll position
   *     changes.
   * @param {Window|Element} element The element to listen for scroll events on. Defaults to
   *    `window`.
   */
  constructor(callback, element = window) {
    this.callback = callback;
    this.ticking = false;
    this.element = element;
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
    requestAnimationFrame(() => {
      this.update();
      this.ticking = false;
    });

    this.ticking = true;
  }

  /**
   * Run the callback, whether there was a scroll event or not.
   */
  update() {
    this.callback(this.element.pageYOffset);
  }

  /**
   * Start listening to and handling the element's scroll position.
   */
  start() {
    if (!this.active) {
      this.element.addEventListener('scroll', (this.active = this.loop.bind(this)), { passive: true });
    }
  }

  /**
   * Stop listening to and handling the element's scroll position.
   */
  stop() {
    this.element.removeEventListener('scroll', this.active);

    this.active = null;
  }
}
