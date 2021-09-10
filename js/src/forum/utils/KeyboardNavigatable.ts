type KeyboardEventHandler = (event: KeyboardEvent) => void;
type ShouldHandle = (event: KeyboardEvent) => boolean;

enum Keys {
  Enter = 13,
  Escape = 27,
  Space = 32,
  ArrowUp = 38,
  ArrowDown = 40,
  ArrowLeft = 37,
  ArrowRight = 39,
  Tab = 9,
  Backspace = 8,
}

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
    this.callbacks.set(Keys.ArrowUp, (e) => {
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
    this.callbacks.set(Keys.ArrowDown, (e) => {
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * Provide a callback to be executed when the current item is selected..
   *
   * This will be triggered by the Return key (and Tab key, if not disabled).
   */
  onSelect(callback: KeyboardEventHandler, ignoreTabPress: boolean = false): KeyboardNavigatable {
    const handler: KeyboardEventHandler = (e) => {
      e.preventDefault();
      callback(e);
    };

    if (!ignoreTabPress) this.callbacks.set(Keys.Tab, handler);
    this.callbacks.set(Keys.Enter, handler);

    return this;
  }

  /**
   * Provide a callback to be executed when the current item is tabbed into.
   *
   * This will be triggered by the Tab key.
   */
  onTab(callback: KeyboardEventHandler): KeyboardNavigatable {
    const handler: KeyboardEventHandler = (e) => {
      e.preventDefault();
      callback(e);
    };

    this.callbacks.set(9, handler);

    return this;
  }

  /**
   * Provide a callback to be executed when the navigation is canceled.
   *
   * This will be triggered by the Escape key.
   */
  onCancel(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.Escape, (e) => {
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
   */
  onRemove(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.Backspace, (e) => {
      if (e instanceof KeyboardEvent && e.target instanceof HTMLInputElement && e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
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
  bindTo($element: JQuery<HTMLElement>) {
    // Handle navigation key events on the navigatable element.
    $element[0].addEventListener('keydown', this.navigate.bind(this));
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
