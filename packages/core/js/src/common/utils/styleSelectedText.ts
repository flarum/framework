/*
 * Original Copyright GitHub, Inc. Licensed under the MIT License.
 * See license text at https://github.com/github/markdown-toolbar-element/blob/master/LICENSE.
 */

import insertText, { SelectionRange } from './insertText';

interface StyleArgs {
  prefix: string;
  suffix: string;
  blockPrefix: string;
  blockSuffix: string;
  multiline: boolean;
  replaceNext: string;
  prefixSpace: boolean;
  scanFor: string;
  surroundWithNewlines: boolean;
  orderedList: boolean;
  unorderedList: boolean;
  trimFirst: boolean;
}

const defaults: StyleArgs = {
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
  unorderedList: false,
  trimFirst: false,
};

export default function styleSelectedText(textarea: HTMLTextAreaElement, styleArgs: StyleArgs) {
  // Next 2 lines are added
  textarea.focus();
  styleArgs = Object.assign({}, defaults, styleArgs);
  // Prev 2 lines are added
  const text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);

  let result;
  if (styleArgs.orderedList || styleArgs.unorderedList) {
    result = listStyle(textarea, styleArgs);
  } else if (styleArgs.multiline && isMultipleLines(text)) {
    result = multilineStyle(textarea, styleArgs);
  } else {
    result = blockStyle(textarea, styleArgs);
  }

  insertText(textarea, result);
}

function isMultipleLines(string: string): boolean {
  return string.trim().split('\n').length > 1;
}

function repeat(string: string, n: number): string {
  return Array(n + 1).join(string);
}

function wordSelectionStart(text: string, i: number): number {
  let index = i;
  while (text[index] && text[index - 1] != null && !text[index - 1].match(/\s/)) {
    index--;
  }
  return index;
}

function wordSelectionEnd(text: string, i: number, multiline: boolean): number {
  let index = i;
  const breakpoint = multiline ? /\n/ : /\s/;
  while (text[index] && !text[index].match(breakpoint)) {
    index++;
  }
  return index;
}

function expandSelectionToLine(textarea: HTMLTextAreaElement) {
  const lines = textarea.value.split('\n');
  let counter = 0;
  for (let index = 0; index < lines.length; index++) {
    const lineLength = lines[index].length + 1;
    if (textarea.selectionStart >= counter && textarea.selectionStart < counter + lineLength) {
      textarea.selectionStart = counter;
    }
    if (textarea.selectionEnd >= counter && textarea.selectionEnd < counter + lineLength) {
      textarea.selectionEnd = counter + lineLength - 1;
    }
    counter += lineLength;
  }
}

function expandSelectedText(textarea: HTMLTextAreaElement, prefixToUse: string, suffixToUse: string, multiline = false): string {
  if (textarea.selectionStart === textarea.selectionEnd) {
    textarea.selectionStart = wordSelectionStart(textarea.value, textarea.selectionStart);
    textarea.selectionEnd = wordSelectionEnd(textarea.value, textarea.selectionEnd, multiline);
  } else {
    const expandedSelectionStart = textarea.selectionStart - prefixToUse.length;
    const expandedSelectionEnd = textarea.selectionEnd + suffixToUse.length;
    const beginsWithPrefix = textarea.value.slice(expandedSelectionStart, textarea.selectionStart) === prefixToUse;
    const endsWithSuffix = textarea.value.slice(textarea.selectionEnd, expandedSelectionEnd) === suffixToUse;
    if (beginsWithPrefix && endsWithSuffix) {
      textarea.selectionStart = expandedSelectionStart;
      textarea.selectionEnd = expandedSelectionEnd;
    }
  }
  return textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
}

interface Newlines {
  newlinesToAppend: string;
  newlinesToPrepend: string;
}

function newlinesToSurroundSelectedText(textarea: HTMLTextAreaElement): Newlines {
  const beforeSelection = textarea.value.slice(0, textarea.selectionStart);
  const afterSelection = textarea.value.slice(textarea.selectionEnd);

  const breaksBefore = beforeSelection.match(/\n*$/);
  const breaksAfter = afterSelection.match(/^\n*/);
  const newlinesBeforeSelection = breaksBefore ? breaksBefore[0].length : 0;
  const newlinesAfterSelection = breaksAfter ? breaksAfter[0].length : 0;

  let newlinesToAppend;
  let newlinesToPrepend;

  if (beforeSelection.match(/\S/) && newlinesBeforeSelection < 2) {
    newlinesToAppend = repeat('\n', 2 - newlinesBeforeSelection);
  }

  if (afterSelection.match(/\S/) && newlinesAfterSelection < 2) {
    newlinesToPrepend = repeat('\n', 2 - newlinesAfterSelection);
  }

  if (newlinesToAppend == null) {
    newlinesToAppend = '';
  }

  if (newlinesToPrepend == null) {
    newlinesToPrepend = '';
  }

  return { newlinesToAppend, newlinesToPrepend };
}

function blockStyle(textarea: HTMLTextAreaElement, arg: StyleArgs): SelectionRange {
  let newlinesToAppend;
  let newlinesToPrepend;

  const { prefix, suffix, blockPrefix, blockSuffix, replaceNext, prefixSpace, scanFor, surroundWithNewlines } = arg;
  const originalSelectionStart = textarea.selectionStart;
  const originalSelectionEnd = textarea.selectionEnd;

  let selectedText = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let prefixToUse = isMultipleLines(selectedText) && blockPrefix.length > 0 ? `${blockPrefix}\n` : prefix;
  // CHANGED
  let suffixToUse = isMultipleLines(selectedText) && blockSuffix.length > 0 ? `\n${blockSuffix}` : prefixToUse === prefix ? suffix : '';
  // END CHANGED

  if (prefixSpace) {
    const beforeSelection = textarea.value[textarea.selectionStart - 1];
    if (textarea.selectionStart !== 0 && beforeSelection != null && !beforeSelection.match(/\s/)) {
      prefixToUse = ` ${prefixToUse}`;
    }
  }
  selectedText = expandSelectedText(textarea, prefixToUse, suffixToUse, arg.multiline);
  let selectionStart = textarea.selectionStart;
  let selectionEnd = textarea.selectionEnd;
  const hasReplaceNext = replaceNext.length > 0 && suffixToUse.indexOf(replaceNext) > -1 && selectedText.length > 0;
  if (surroundWithNewlines) {
    const ref = newlinesToSurroundSelectedText(textarea);
    newlinesToAppend = ref.newlinesToAppend;
    newlinesToPrepend = ref.newlinesToPrepend;
    prefixToUse = newlinesToAppend + prefix;
    suffixToUse += newlinesToPrepend;
  }

  if (selectedText.startsWith(prefixToUse) && selectedText.endsWith(suffixToUse)) {
    const replacementText = selectedText.slice(prefixToUse.length, selectedText.length - suffixToUse.length);
    if (originalSelectionStart === originalSelectionEnd) {
      let position = originalSelectionStart - prefixToUse.length;
      position = Math.max(position, selectionStart);
      position = Math.min(position, selectionStart + replacementText.length);
      selectionStart = selectionEnd = position;
    } else {
      selectionEnd = selectionStart + replacementText.length;
    }
    return { text: replacementText, selectionStart, selectionEnd };
  } else if (!hasReplaceNext) {
    let replacementText = prefixToUse + selectedText + suffixToUse;
    selectionStart = originalSelectionStart + prefixToUse.length;
    selectionEnd = originalSelectionEnd + prefixToUse.length;
    const whitespaceEdges = selectedText.match(/^\s*|\s*$/g);
    if (arg.trimFirst && whitespaceEdges) {
      const leadingWhitespace = whitespaceEdges[0] || '';
      const trailingWhitespace = whitespaceEdges[1] || '';
      replacementText = leadingWhitespace + prefixToUse + selectedText.trim() + suffixToUse + trailingWhitespace;
      selectionStart += leadingWhitespace.length;
      selectionEnd -= trailingWhitespace.length;
    }
    return { text: replacementText, selectionStart, selectionEnd };
  } else if (scanFor.length > 0 && selectedText.match(scanFor)) {
    suffixToUse = suffixToUse.replace(replaceNext, selectedText);
    const replacementText = prefixToUse + suffixToUse;
    selectionStart = selectionEnd = selectionStart + prefixToUse.length;
    return { text: replacementText, selectionStart, selectionEnd };
  } else {
    const replacementText = prefixToUse + selectedText + suffixToUse;
    selectionStart = selectionStart + prefixToUse.length + selectedText.length + suffixToUse.indexOf(replaceNext);
    selectionEnd = selectionStart + replaceNext.length;
    return { text: replacementText, selectionStart, selectionEnd };
  }
}

function multilineStyle(textarea: HTMLTextAreaElement, arg: StyleArgs) {
  const { prefix, suffix, blockPrefix, blockSuffix, surroundWithNewlines } = arg;
  let text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let selectionStart = textarea.selectionStart;
  let selectionEnd = textarea.selectionEnd;
  const lines = text.split('\n');
  // CHANGED
  let prefixToUse = blockPrefix.length > 0 ? blockPrefix : prefix;
  let suffixToUse = blockSuffix.length > 0 ? blockSuffix : prefixToUse == prefix ? suffix : '';
  const undoStyle = lines.every((line) => line.startsWith(prefixToUse) && line.endsWith(suffixToUse));
  // END CHANGED

  if (undoStyle) {
    text = lines.map((line) => line.slice(prefixToUse.length, line.length - suffixToUse.length)).join('\n');
    selectionEnd = selectionStart + text.length;
  } else {
    // CHANGED
    text = lines.map((line) => prefixToUse + line + suffixToUse).join('\n');
    if (surroundWithNewlines || suffixToUse === '') {
      // END CHANGED
      const { newlinesToAppend, newlinesToPrepend } = newlinesToSurroundSelectedText(textarea);
      selectionStart += newlinesToAppend.length;
      selectionEnd = selectionStart + text.length;
      text = newlinesToAppend + text + newlinesToPrepend;
    }
  }

  return { text, selectionStart, selectionEnd };
}

interface UndoResult {
  text: string;
  processed: boolean;
}
function undoOrderedListStyle(text: string): UndoResult {
  const lines = text.split('\n');
  const orderedListRegex = /^\d+\.\s+/;
  const shouldUndoOrderedList = lines.every((line) => orderedListRegex.test(line));
  let result = lines;
  if (shouldUndoOrderedList) {
    result = lines.map((line) => line.replace(orderedListRegex, ''));
  }

  return {
    text: result.join('\n'),
    processed: shouldUndoOrderedList,
  };
}

function undoUnorderedListStyle(text: string): UndoResult {
  const lines = text.split('\n');
  const unorderedListPrefix = '- ';
  const shouldUndoUnorderedList = lines.every((line) => line.startsWith(unorderedListPrefix));
  let result = lines;
  if (shouldUndoUnorderedList) {
    result = lines.map((line) => line.slice(unorderedListPrefix.length, line.length));
  }

  return {
    text: result.join('\n'),
    processed: shouldUndoUnorderedList,
  };
}

function makePrefix(index: number, unorderedList: boolean): string {
  if (unorderedList) {
    return '- ';
  } else {
    return `${index + 1}. `;
  }
}

function clearExistingListStyle(style: StyleArgs, selectedText: string): [UndoResult, UndoResult, string] {
  let undoResultOpositeList: UndoResult;
  let undoResult: UndoResult;
  let pristineText;
  if (style.orderedList) {
    undoResult = undoOrderedListStyle(selectedText);
    undoResultOpositeList = undoUnorderedListStyle(undoResult.text);
    pristineText = undoResultOpositeList.text;
  } else {
    undoResult = undoUnorderedListStyle(selectedText);
    undoResultOpositeList = undoOrderedListStyle(undoResult.text);
    pristineText = undoResultOpositeList.text;
  }
  return [undoResult, undoResultOpositeList, pristineText];
}

function listStyle(textarea: HTMLTextAreaElement, style: StyleArgs): SelectionRange {
  const noInitialSelection = textarea.selectionStart === textarea.selectionEnd;
  let selectionStart = textarea.selectionStart;
  let selectionEnd = textarea.selectionEnd;

  // Select whole line
  expandSelectionToLine(textarea);

  const selectedText = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);

  // If the user intent was to do an undo, we will stop after this.
  // Otherwise, we will still undo to other list type to prevent list stacking
  const [undoResult, undoResultOpositeList, pristineText] = clearExistingListStyle(style, selectedText);

  const prefixedLines = pristineText.split('\n').map((value, index) => {
    return `${makePrefix(index, style.unorderedList)}${value}`;
  });

  const totalPrefixLength = prefixedLines.reduce((previousValue, _currentValue, currentIndex) => {
    return previousValue + makePrefix(currentIndex, style.unorderedList).length;
  }, 0);

  const totalPrefixLengthOpositeList = prefixedLines.reduce((previousValue, _currentValue, currentIndex) => {
    return previousValue + makePrefix(currentIndex, !style.unorderedList).length;
  }, 0);

  if (undoResult.processed) {
    if (noInitialSelection) {
      selectionStart = Math.max(selectionStart - makePrefix(0, style.unorderedList).length, 0);
      selectionEnd = selectionStart;
    } else {
      selectionStart = textarea.selectionStart;
      selectionEnd = textarea.selectionEnd - totalPrefixLength;
    }
    return { text: pristineText, selectionStart, selectionEnd };
  }

  const { newlinesToAppend, newlinesToPrepend } = newlinesToSurroundSelectedText(textarea);
  const text = newlinesToAppend + prefixedLines.join('\n') + newlinesToPrepend;

  if (noInitialSelection) {
    selectionStart = Math.max(selectionStart + makePrefix(0, style.unorderedList).length + newlinesToAppend.length, 0);
    selectionEnd = selectionStart;
  } else {
    if (undoResultOpositeList.processed) {
      selectionStart = Math.max(textarea.selectionStart + newlinesToAppend.length, 0);
      selectionEnd = textarea.selectionEnd + newlinesToAppend.length + totalPrefixLength - totalPrefixLengthOpositeList;
    } else {
      selectionStart = Math.max(textarea.selectionStart + newlinesToAppend.length, 0);
      selectionEnd = textarea.selectionEnd + newlinesToAppend.length + totalPrefixLength;
    }
  }

  return { text, selectionStart, selectionEnd };
}
