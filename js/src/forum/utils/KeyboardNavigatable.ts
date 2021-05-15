type KeyboardEventHandler = (event: KeyboardEvent) => void;
type ShouldHandle = (event: KeyboardEvent) => boolean;

/**
 * The `KeyboardNavigatable` class manages lists that can be navigated with the
 * keyboard, calling callbacks for each actions.
 *
 * This helper encapsulates the key binding logic, providing a simple fluent
 * API for use.
 */
export default class KeyboardNavigatable {
  /**
   * Callback to be executed for a specified input.
   */
  protected callbacks = new Map<number, KeyboardEventHandler>();

  /**
   * Callback that determines whether keyboard input should be handled.
   * By default, always handle keyboard navigation.
   */
  protected whenCallback: ShouldHandle = (event: KeyboardEvent) => true;

  /**
   * Provide a callback to be executed when navigating upwards.
   *
   * This will be triggered by the Up key.
   */
  onUp(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(38, (e) => {
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * Provide a callback to be executed when navigating downwards.
   *
   * This will be triggered by the Down key.
   */
  onDown(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(40, (e) => {
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * Provide a callback to be executed when the current item is selected..
   *
   * This will be triggered by the Return and Tab keys..
   */
  onSelect(callback: KeyboardEventHandler): KeyboardNavigatable {
    const handler: KeyboardEventHandler = (e) => {
      e.preventDefault();
      callback(e);
    };

    this.callbacks.set(9, handler);
    this.callbacks.set(13, handler);

    return this;
  }

  /**
   * Provide a callback to be executed when the navigation is canceled.
   *
   * This will be triggered by the Escape key.
   */
  onCancel(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(27, (e) => {
      e.stopPropagation();
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * Provide a callback to be executed when previous input is removed.
   *
   * This will be triggered by the Backspace key.
   *
   * @public
   * @param {KeyboardNavigatable~keyCallback} callback
   * @return {KeyboardNavigatable}
   */
  onRemove(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(8, (e) => {
      if (e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
        callback(e);
        e.preventDefault();
      }
    });

    return this;
  }

  /**
   * Provide a callback that determines whether keyboard input should be handled.
   */
  when(callback: ShouldHandle): KeyboardNavigatable {
    this.whenCallback = callback;

    return this;
  }

  /**
   * Set up the navigation key bindings on the given jQuery element.
   */
  bindTo($element: JQuery) {
    // Handle navigation key events on the navigatable element.
    $element.on('keydown', this.navigate.bind(this));
  }

  /**
   * Interpret the given keyboard event as navigation commands.
   */
  navigate(event: KeyboardEvent) {
    // This callback determines whether keyboard should be handled or ignored.
    if (!this.whenCallback(event)) return;

    const keyCallback = this.callbacks.get(event.which);
    if (keyCallback) {
      keyCallback(event);
    }
  }
}
