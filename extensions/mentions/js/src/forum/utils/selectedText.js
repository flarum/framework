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

      // A link to a discussion on this forum is shown as a label (a favicon,
      // "#38941" and the post number) standing in for the address the writer
      // pasted, which is the link's href. Quote that address rather than the
      // pieces of the label. Keep in step with Formatter::getDiscussionLinkTemplate().
      clone.find('a.UrlLink--discussion').replaceWith(function () {
        // A text node, so that the address is not parsed as HTML.
        return document.createTextNode(this.getAttribute('href'));
      });

      // Replace emoji images with their shortcode (found in alt attribute)
      clone.find('img.emoji').replaceWith(function () {
        return this.alt;
      });

      // Replace all other images with a Markdown image
      clone.find('img').replaceWith(function () {
        return `![](${this.src})`;
      });

      // Replace all links with a Markdown link
      clone.find('a').replaceWith(function () {
        return `[${this.innerText}](${this.href})`;
      });

      return clone.text();
    }
  }
  return '';
}
