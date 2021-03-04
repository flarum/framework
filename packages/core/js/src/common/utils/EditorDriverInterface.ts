export interface EditorDriverParams {
  /**
   * An array of HTML class names to apply to the editor's main DOM element.
   */
  classNames: string[];

  /**
   * Whether the editor should be initially disabled.
   */
  disabled: boolean;

  /**
   * An optional placeholder for the editor.
   */
  placeholder: string;

  /**
   * An optional initial value for the editor.
   */
  value: string;

  /**
   * This is separate from inputListeners since the full serialized content will be passed to it.
   * It is considered private API, and should not be used/modified by extensions not implementing
   * EditorDriverInterface.
   */
  oninput: Function;

  /**
   * Each of these functions will be called on click, input, and keyup.
   * No arguments will be passed.
   */
  inputListeners: Function[];

  /**
   * This function will be called if submission is triggered programmatically via keybind.
   * No arguments should be passed.
   */
  onsubmit: Function;
}

export default interface EditorDriverInterface {
  /**
   * Focus the editor and place the cursor at the given position.
   */
  moveCursorTo(position: number): void;

  /**
   * Get the selected range of the editor.
   */
  getSelectionRange(): Array<number>;

  /**
   * Get the last N characters from the current "text block".
   *
   * A textarea-based driver would just return the last N characters,
   * but more advanced implementations might restrict to the current block.
   *
   * This is useful for monitoring recent user input to trigger autocomplete.
   */
  getLastNChars(n: number): string;

  /**
   * Insert content into the editor at the position of the cursor.
   */
  insertAtCursor(text: string, escape: boolean): void;

  /**
   * Insert content into the editor at the given position.
   */
  insertAt(pos: number, text: string, escape: boolean): void;

  /**
   * Insert content into the editor between the given positions.
   *
   * If the start and end positions are different, any text between them will be
   * overwritten.
   */
  insertBetween(start: number, end: number, text: string, escape: boolean): void;

  /**
   * Replace existing content from the start to the current cursor position.
   */
  replaceBeforeCursor(start: number, text: string, escape: boolean): void;

  /**
   * Get left and top coordinates of the caret relative to the editor viewport.
   */
  getCaretCoordinates(position: number): { left: number; top: number };

  /**
   * Set the disabled status of the editor.
   */
  disabled(disabled: boolean): void;

  /**
   * Focus on the editor.
   */
  focus(): void;

  /**
   * Destroy the editor
   */
  destroy(): void;
}
