/*
 * Original Copyright GitHub, Inc. Licensed under the MIT License.
 * See license text at https://github.com/github/markdown-toolbar-element/blob/master/LICENSE.
 */

import insertText from './insertText';
import { blockStyle, isMultipleLines, multilineStyle, orderedList } from './styles';

export const styleSelectedText = (textarea, styleArgs) => {
  const text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let result;
  if (styleArgs.orderedList) {
    result = orderedList(textarea);
  }
  else if (styleArgs.multiline && isMultipleLines(text)) {
    result = multilineStyle(textarea, styleArgs);
  }
  else {
    result = blockStyle(textarea, styleArgs);
  }

  insertText(textarea, result);
};

export default (button, stylesToApply) => {
  const toolbar = button.parentElement;

  const defaults = {
    prefix: '',
    suffix: '',
    blockPrefix: '',
    blockSuffix: '',
    multiline: false,
    replaceNext: '',
    prefixSpace: false,
    scanFor: '',
    surroundWithNewlines: false,
    orderedList: false,
    trimFirst: false
  };

  const style = Object.assign({}, defaults, stylesToApply);

  const field = document.getElementById(toolbar.dataset.for);

  if (field) {
    field.focus();
    styleSelectedText(field, style);
  }
}
