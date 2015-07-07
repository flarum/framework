import truncate from '../utils/truncate';

export default function(string, phrase, length) {
  if (!phrase) {
    return string;
  }

  const regexp = regexp instanceof RegExp ? phrase : new RegExp(phrase, 'gi');

  let highlightedString = string;
  let start = 0;

  if (length) {
    start = Math.max(0, string.search(regexp) - length / 2);
    highlightedString = truncate(highlightedString, length, start);
  }

  highlightedString = $('<div/>').text(highlightedString).html().replace(regexp, '<mark>$&</mark>');

  return m.trust(highlightedString);
}
