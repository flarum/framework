/*
 * Original Copyright GitHub, Inc. Licensed under the MIT License.
 * See license text at https://github.com/github/markdown-toolbar-element/blob/master/LICENSE.
 */

export interface SelectionRange {
  text: string;
  selectionStart: number | undefined;
  selectionEnd: number | undefined;
}

let canInsertText: boolean | null = null;

export default function insertText(textarea: HTMLTextAreaElement, { text, selectionStart, selectionEnd }: SelectionRange) {
  const originalSelectionStart = textarea.selectionStart;
  const before = textarea.value.slice(0, originalSelectionStart);
  const after = textarea.value.slice(textarea.selectionEnd);

  if (selectionStart != null && selectionEnd != null) {
    textarea.setSelectionRange(selectionStart, selectionEnd + 1);
  } else {
    textarea.setSelectionRange(originalSelectionStart, textarea.selectionEnd);
  }
  textarea.focus();

  if (canInsertText === null || canInsertText === true) {
    textarea.contentEditable = 'true';
    canInsertText = document.execCommand('insertText', false, text);
    textarea.contentEditable = 'false';
  }

  if (canInsertText && !textarea.value.slice(0, textarea.selectionStart).endsWith(text)) {
    canInsertText = false;
  }

  if (!canInsertText) {
    textarea.value = before + text + after;
    textarea.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }
}
