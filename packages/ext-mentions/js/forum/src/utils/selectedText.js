export default function selectedText(body) {
  const selection = window.getSelection();
  if (selection.rangeCount) {
    const range = selection.getRangeAt(0);
    const parent = range.commonAncestorContainer;
    if (body[0] === parent || $.contains(body[0], parent)) {
      const clone = $("<div>").append(range.cloneContents());
      clone.find('img.emoji').replaceWith(function() {
        return this.alt;
      });
      return clone.text();
    }
  }
  return "";
}
