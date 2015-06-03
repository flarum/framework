export default function(string, regexp) {
  if (!regexp) {
    return string;
  }

  if (!(regexp instanceof RegExp)) {
    regexp = new RegExp(regexp, 'gi');
  }

  return m.trust(
    $('<div/>').text(string).html().replace(regexp, '<mark>$&</mark>')
  );
}
