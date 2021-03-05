import getCaretCoordinates from 'textarea-caret';
import EditorDriverInterface, { EditorDriverParams } from './EditorDriverInterface';

export default class BasicEditorDriver implements EditorDriverInterface {
  el: HTMLTextAreaElement;

  constructor(dom: HTMLElement, params: EditorDriverParams) {
    this.el = document.createElement('textarea');

    this.build(dom, params);
  }

  build(dom: HTMLElement, params: EditorDriverParams) {
    this.el.className = params.classNames.join(' ');
    this.el.disabled = params.disabled;
    this.el.placeholder = params.placeholder;
    this.el.value = params.value;

    const callInputListeners = (e) => {
      params.inputListeners.forEach((listener) => {
        listener();
      });

      e.redraw = false;
    };

    this.el.oninput = (e) => {
      params.oninput(this.el.value);
      callInputListeners(e);
    };

    this.el.onclick = callInputListeners;
    this.el.onkeyup = callInputListeners;

    this.el.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        params.onsubmit();
      }
    });

    dom.append(this.el);
  }

  protected setValue(value: string) {
    $(this.el).val(value).trigger('input');

    this.el.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }

  moveCursorTo(position: number) {
    this.setSelectionRange(position, position);
  }

  getSelectionRange(): Array<number> {
    return [this.el.selectionStart, this.el.selectionEnd];
  }

  getLastNChars(n: number): string {
    const value = this.el.value;

    return value.slice(Math.max(0, this.el.selectionStart - n), this.el.selectionStart);
  }

  insertAtCursor(text: string) {
    this.insertAt(this.el.selectionStart, text);
  }

  insertAt(pos: number, text: string) {
    this.insertBetween(pos, pos, text);
  }

  insertBetween(start: number, end: number, text: string) {
    const value = this.el.value;

    const before = value.slice(0, start);
    const after = value.slice(end);

    this.setValue(`${before}${text}${after}`);

    // Move the textarea cursor to the end of the content we just inserted.
    this.moveCursorTo(start + text.length);
  }

  replaceBeforeCursor(start: number, text: string) {
    this.insertBetween(start, this.el.selectionStart, text);
  }

  protected setSelectionRange(start: number, end: number) {
    this.el.setSelectionRange(start, end);
    this.focus();
  }

  getCaretCoordinates(position: number) {
    const relCoords = getCaretCoordinates(this.el, position);

    return {
      top: relCoords.top - this.el.scrollTop,
      left: relCoords.left,
    };
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
