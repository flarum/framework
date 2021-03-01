/**
 * A textarea wrapper with powerful helpers for text manipulation.
 *
 * This wraps a <textarea> DOM element and allows directly manipulating its text
 * contents and cursor positions.
 *
 * I apologize for the pretentious name. :)
 */
export default class SuperTextarea {
  /**
   * @param {HTMLTextAreaElement} textarea
   */
  constructor(textarea) {
    this.el = textarea;
    this.$ = $(textarea);
  }

  /**
   * Set the value of the text editor.
   *
   * @param {String} value
   */
  setValue(value) {
    this.$.val(value).trigger('input');

    this.el.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }

  /**
   * Focus the textarea and place the cursor at the given index.
   *
   * @param {number} position
   */
  moveCursorTo(position) {
    this.setSelectionRange(position, position);
  }

  /**
   * Get the selected range of the textarea.
   *
   * @return {Array}
   */
  getSelectionRange() {
    return [this.el.selectionStart, this.el.selectionEnd];
  }

  /**
   * Insert content into the textarea at the position of the cursor.
   *
   * @param {String} text
   */
  insertAtCursor(text) {
    this.insertAt(this.el.selectionStart, text);
  }

  /**
   * Insert content into the textarea at the given position.
   *
   * @param {number} pos
   * @param {String} text
   */
  insertAt(pos, text) {
    this.insertBetween(pos, pos, text);
  }

  /**
   * Insert content into the textarea between the given positions.
   *
   * If the start and end positions are different, any text between them will be
   * overwritten.
   *
   * @param start
   * @param end
   * @param text
   */
  insertBetween(start, end, text) {
    const value = this.el.value;

    const before = value.slice(0, start);
    const after = value.slice(end);

    this.setValue(`${before}${text}${after}`);

    // Move the textarea cursor to the end of the content we just inserted.
    this.moveCursorTo(start + text.length);
  }

  /**
   * Replace existing content from the start to the current cursor position.
   *
   * @param start
   * @param text
   */
  replaceBeforeCursor(start, text) {
    this.insertBetween(start, this.el.selectionStart, text);
  }

  /**
   * Set the selected range of the textarea.
   *
   * @param {number} start
   * @param {number} end
   * @private
   */
  setSelectionRange(start, end) {
    this.el.setSelectionRange(start, end);
    this.$.focus();
  }
}
