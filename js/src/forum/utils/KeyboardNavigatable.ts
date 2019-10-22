/**
 * The `KeyboardNavigatable` class manages lists that can be navigated with the
 * keyboard, calling callbacks for each actions.
 *
 * This helper encapsulates the key binding logic, providing a simple fluent
 * API for use.
 */
export default class KeyboardNavigatable {
  callbacks = {};

  // By default, always handle keyboard navigation.
  whenCallback = () => true;

  /**
   * Provide a callback to be executed when navigating upwards.
   *
   * This will be triggered by the Up key.
   */
  onUp(callback: Function): this {
    this.callbacks[38] = e => {
      e.preventDefault();
      callback(e);
    };

    return this;
  }

  /**
   * Provide a callback to be executed when navigating downwards.
   *
   * This will be triggered by the Down key.
   */
  onDown(callback: Function): this {
    this.callbacks[40] = e => {
      e.preventDefault();
      callback(e);
    };

    return this;
  }

  /**
   * Provide a callback to be executed when the current item is selected..
   *
   * This will be triggered by the Return and Tab keys..
   */
  onSelect(callback: Function): this {
    this.callbacks[9] = this.callbacks[13] = e => {
      e.preventDefault();
      callback(e);
    };

    return this;
  }

  /**
   * Provide a callback to be executed when the navigation is canceled.
   *
   * This will be triggered by the Escape key.
   */
  onCancel(callback: Function): this {
    this.callbacks[27] = e => {
      e.stopPropagation();
      e.preventDefault();
      callback(e);
    };

    return this;
  }

  /**
   * Provide a callback to be executed when previous input is removed.
   *
   * This will be triggered by the Backspace key.
   */
  onRemove(callback: Function): this {
    this.callbacks[8] = e => {
      if (e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
        callback(e);
        e.preventDefault();
      }
    };

    return this;
  }

  /**
   * Provide a callback that determines whether keyboard input should be handled.
   */
  when(callback: () => boolean): this {
    this.whenCallback = callback;

    return this;
  }

  /**
   * Set up the navigation key bindings on the given jQuery element.
   */
  bindTo($element: any) {
    // Handle navigation key events on the navigatable element.
    $element.on('keydown', this.navigate.bind(this));
  }

  /**
   * Interpret the given keyboard event as navigation commands.
   */
  navigate(event: KeyboardEvent) {
    // This callback determines whether keyboard should be handled or ignored.
    if (!this.whenCallback()) return;

    const keyCallback = this.callbacks[event.which];
    if (keyCallback) {
      keyCallback(event);
    }
  }
}
