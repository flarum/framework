/*
 * Original Copyright GitHub, Inc. Licensed under the MIT License.
 * See license text at https://github.com/github/markdown-toolbar-element/blob/master/LICENSE.
 */

export let canInsertText = null;

export default (textarea, { text, selectionStart, selectionEnd }) => {
  const originalSelectionStart = textarea.selectionStart;
  const before = textarea.value.slice(0, originalSelectionStart);
  const after = textarea.value.slice(textarea.selectionEnd);

  if (canInsertText === null || canInsertText === true) {
    textarea.contentEditable = 'true';
    try {
      canInsertText = document.execCommand('insertText', false, text);
    }
    catch (error) {
      canInsertText = false;
    }
    textarea.contentEditable = 'false';
  }
  if (canInsertText && !textarea.value.slice(0, textarea.selectionStart).endsWith(text)) {
    canInsertText = false;
  }
  if (!canInsertText) {
    try {
      document.execCommand('ms-beginUndoUnit');
    }
    catch (e) {
      // Do nothing.
    }
    textarea.value = before + text + after;
    try {
      document.execCommand('ms-endUndoUnit');
    }
    catch (e) {
      // Do nothing.
    }

    // fire custom event, works on IE
    const event = document.createEvent('Event');

    event.initEvent('input', true, true);

    textarea.dispatchEvent(event);
  }
  if (selectionStart != null && selectionEnd != null) {
    textarea.setSelectionRange(selectionStart, selectionEnd);
  }
  else {
    textarea.setSelectionRange(originalSelectionStart, textarea.selectionEnd);
  }
};
