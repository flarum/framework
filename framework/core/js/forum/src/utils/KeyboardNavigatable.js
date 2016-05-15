/**
 * The `KeyboardNavigatable` class manages lists that can be navigated with the
 * keyboard, calling callbacks for each actions.
 *
 * This helper encapsulates the key binding logic, providing a simple fluent
 * API for use.
 */
export default class KeyboardNavigatable {
  constructor() {
    const defaultCallback = () => { /* noop */ };

    // Set all callbacks to a noop function so that not all of them have to be set.
    this.upCallback = defaultCallback;
    this.downCallback = defaultCallback;
    this.selectCallback = defaultCallback;
    this.cancelCallback = defaultCallback;

    // By default, always handle keyboard navigation.
    this.whenCallback = () => true;
  }

  /**
   * Provide a callback to be executed when navigating upwards.
   *
   * This will be triggered by the Up key.
   *
   * @public
   * @param {Function} callback
   * @return {KeyboardNavigatable}
   */
  onUp(callback) {
    this.upCallback = callback;

    return this;
  }

  /**
   * Provide a callback to be executed when navigating downwards.
   *
   * This will be triggered by the Down key.
   *
   * @public
   * @param {Function} callback
   * @return {KeyboardNavigatable}
   */
  onDown(callback) {
    this.downCallback = callback;

    return this;
  }

  /**
   * Provide a callback to be executed when the current item is selected..
   *
   * This will be triggered by the Return and Tab keys..
   *
   * @public
   * @param {Function} callback
   * @return {KeyboardNavigatable}
   */
  onSelect(callback) {
    this.selectCallback = callback;

    return this;
  }

  /**
   * Provide a callback to be executed when the navigation is canceled.
   *
   * This will be triggered by the Escape key.
   *
   * @public
   * @param {Function} callback
   * @return {KeyboardNavigatable}
   */
  onCancel(callback) {
    this.cancelCallback = callback;

    return this;
  }

  /**
   * Provide a callback that determines whether keyboard input should be handled.
   *
   * @public
   * @param {Function} callback
   * @return {KeyboardNavigatable}
   */
  when(callback) {
    this.whenCallback = callback;

    return this;
  }

  /**
   * Set up the navigation key bindings on the given jQuery element.
   *
   * @public
   * @param {jQuery} $element
   */
  bindTo($element) {
    // Handle navigation key events on the navigatable element.
    $element.on('keydown', this.navigate.bind(this));
  }

  /**
   * Interpret the given keyboard event as navigation commands.
   *
   * @public
   * @param {KeyboardEvent} event
   */
  navigate(event) {
    // This callback determines whether keyboard should be handled or ignored.
    if (!this.whenCallback()) return;

    switch (event.which) {
      case 9: case 13: // Tab / Return
        this.selectCallback();
        event.preventDefault();
        break;

      case 27: // Escape
        this.cancelCallback();
        event.stopPropagation();
        event.preventDefault();
        break;

      case 38: // Up
        this.upCallback();
        event.preventDefault();
        break;

      case 40: // Down
        this.downCallback();
        event.preventDefault();
        break;

      default:
      // no default
    }
  }
}
