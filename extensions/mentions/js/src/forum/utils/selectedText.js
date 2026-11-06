/**
 * Finds the selected text in the provided composer body.
 */
export default function selectedText(body) {
  const selection = window.getSelection();

  if (!selection.isCollapsed) {
    const range = selection.getRangeAt(0);
    const parent = range.commonAncestorContainer;

    if (body[0] === parent || $.contains(body[0], parent)) {
      const clone = $('<div>').append(range.cloneContents());

      // The replacements below are text nodes: a string returned to
      // replaceWith() is parsed as HTML, and the post's text must stay text.

      // Replace emoji images with their shortcode (found in alt attribute)
      clone.find('img.emoji').replaceWith(function () {
        return document.createTextNode(this.alt);
      });

      // Replace all other images with a Markdown image
      clone.find('img').replaceWith(function () {
        return document.createTextNode(`![](${this.src})`);
      });

      // Replace all links with a Markdown link
      clone.find('a').replaceWith(function () {
        return document.createTextNode(`[${this.innerText}](${this.href})`);
      });

      return clone.text();
    }
  }
  return '';
}
