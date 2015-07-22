export default function formatText(text) {
  const elm = document.createElement('div');

  s9e.TextFormatter.preview(text || '', elm);

  return elm.innerHTML;
}
