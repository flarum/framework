import getCaretCoordinates from 'textarea-caret';

export default class BasicEditorDriver {
  onsubmit?: Function;
  el: HTMLTextAreaElement;

  constructor(dom, params) {
    this.el = document.createElement('textarea');

    this.build(dom, params);
  }

  build(dom: HTMLElement, params) {
    this.el.className = params.classNames.join(' ');
    this.el.disabled = params.disabled;
    this.el.placeholder = params.placeholder;
    this.el.value = params.value;
    this.el.oninput = (e) => {
      params.oninput(this.el.value);
      e.redraw = false;
    };

    this.el.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        params.onsubmit();
      }
    });

    dom.append(this.el);
  }

  // CONTENT INTERACTIONS

  /**
   * Set the value of the text editor.
   */
  setValue(value: string) {
    $(this.el).val(value).trigger('input');

    this.el.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }

  /**
   * Focus the textarea and place the cursor at the given index.
   */
  moveCursorTo(position: number) {
    this.setSelectionRange(position, position);
  }

  /**
   * Get the selected range of the textarea.
   */
  getSelectionRange(): Array<number> {
    return [this.el.selectionStart, this.el.selectionEnd];
  }

  /**
   * Get the last N characters from the current "text block".
   * For the textarea driver, this will just return the last N characters.
   */
  getLastNChars(n: number) {
    const value = this.el.value;

    return value.slice(Math.max(0, this.el.selectionStart - n), this.el.selectionStart);
  }

  /**
   * Insert content into the textarea at the position of the cursor.
   */
  insertAtCursor(text: string) {
    this.insertAt(this.el.selectionStart, text);
  }

  /**
   * Insert content into the textarea at the given position.
   */
  insertAt(pos: number, text: string) {
    this.insertBetween(pos, pos, text);
  }

  /**
   * Insert content into the textarea between the given positions.
   *
   * If the start and end positions are different, any text between them will be
   * overwritten.
   */
  insertBetween(start: number, end: number, text: string) {
    const value = this.el.value;

    const before = value.slice(0, start);
    const after = value.slice(end);

    this.setValue(`${before}${text}${after}`);

    // Move the textarea cursor to the end of the content we just inserted.
    this.moveCursorTo(start + text.length);
  }

  /**
   * Replace existing content from the start to the current cursor position.
   */
  replaceBeforeCursor(start: number, text: string) {
    this.insertBetween(start, this.el.selectionStart, text);
  }

  /**
   * Set the selected range of the textarea.
   */
  protected setSelectionRange(start: number, end: number) {
    this.el.setSelectionRange(start, end);
    this.focus();
  }

  getCaretCoordinates(position, options) {
    return getCaretCoordinates(this.el, position, options);
  }

  // DOM Interactions

  /**
   * Set the disabled status of the editor.
   */
  disabled(disabled: boolean) {
    this.el.disabled = disabled;
  }

  /**
   * Focus on the editor.
   */
  focus() {
    this.el.focus();
  }

  /**
   * Destroy the editor
   */
  destroy() {
    this.el.remove();
  }
}
