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
  }

  /**
   * Set the selected range of the textarea.
   *
   * @param {Integer} start
   * @param {Integer} end
   */
  setSelectionRange(start, end) {
    this.el.setSelectionRange(start, end);
    this.$.focus();
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
   * @param {String} insert
   */
  insertAtCursor(insert) {
    const value = this.el.value;
    const index = this.el.selectionStart;

    this.setValue(value.slice(0, index) + insert + value.slice(index));

    // Move the textarea cursor to the end of the content we just inserted.
    const pos = index + insert.length;
    this.setSelectionRange(pos, pos);

    this.el.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }
}
