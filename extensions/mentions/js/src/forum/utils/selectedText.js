const BLOCK_SPOILER = 'details.spoiler';
const INLINE_SPOILER = 'span.spoiler, span[data-spoiler]';
const INLINE_SPOILER_IN_LINK = 'a span.spoiler, a span[data-spoiler]';

// Text in a spoiler is escaped where it is, as text, before any Markdown is
// written around it, so that Markdown written for a spoiler inside it is not
// escaped again. Backslashes first, so that a trailing one does not escape the
// closing "!<". Every "<", not only one after "!", because formatting can put
// the "!" and the "<" of a literal "!<" in different text nodes.
const escapeBlock = (text) => text.replace(/\\/g, '\\\\').replace(/</g, '\\<');

// ">!" at the start of a line opens a block spoiler unless the inline spoiler
// closes on that line with no ">" before it. Escape ">" and keep it on one line
// so that it stays inline wherever the quote puts it.
const escapeInline = (text) =>
  escapeBlock(text)
    .replace(/>/g, '\\>')
    .replace(/\s*\n\s*/g, ' ');

// ">![" would start a Markdown image: a space keeps a leading link a link.
const inlineSpoiler = (text) => (text ? `>!${text.startsWith('[') ? ' ' : ''}${text}!<` : '');

const blockSpoiler = (text) => (text.trim() ? `>! ${text.trim().replace(/\n/g, '\n>! ')}\n` : '');

// The text nodes before the node in the clone, outside it.
function textsBefore(node, root) {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
  const texts = [];

  while (walker.nextNode()) {
    const text = walker.currentNode;

    if (node.contains(text) || node.compareDocumentPosition(text) & Node.DOCUMENT_POSITION_FOLLOWING) break;
    texts.push(text);
  }

  return texts;
}

// An odd number of backslashes just before an inline spoiler would escape its
// opening ">": add one, so that they are all shown and the spoiler opens. The
// run can span several text nodes, which are joined in the quote.
function keepOpening(spoiler, root) {
  const texts = textsBefore(spoiler, root);
  const last = texts[texts.length - 1];
  let run = 0;

  for (let t = texts.length - 1; t >= 0; t--) {
    const data = texts[t].data;
    let i = data.length;

    while (i > 0 && data[i - 1] === '\\') i--;
    run += data.length - i;
    if (i > 0) break;
  }

  if (run % 2) last.data += '\\';
}

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

      // A selection entirely inside a spoiler has none in its fragment: it is
      // wrapped in the spoiler it was taken from below. Only a spoiler inside
      // the post body counts.
      const element = parent.nodeType === Node.ELEMENT_NODE ? parent : parent.parentElement;
      const found = element.closest(`${BLOCK_SPOILER}, ${INLINE_SPOILER}`);
      const around = found && $.contains(body[0], found) ? found : null;
      const aroundInline = around && !around.matches(BLOCK_SPOILER);

      // An inline spoiler inside another is already hidden by it, and its "!<"
      // would close the outer one early: keep only its contents.
      clone
        .find(INLINE_SPOILER)
        .toArray()
        .filter((spoiler) => aroundInline || spoiler.parentElement.closest(INLINE_SPOILER))
        .forEach((spoiler) => spoiler.replaceWith(...spoiler.childNodes));

      const walker = document.createTreeWalker(clone[0], NodeFilter.SHOW_TEXT);
      while (walker.nextNode()) {
        const text = walker.currentNode;
        const container = text.parentElement;

        if (aroundInline || container.closest(INLINE_SPOILER)) text.data = escapeInline(text.data);
        else if (around || container.closest(BLOCK_SPOILER)) text.data = escapeBlock(text.data);
      }

      // A link to a discussion on this forum is shown as a label (a favicon,
      // a title or "#38941", and the post number) standing in for the address the writer
      // pasted, which is the link's href. Quote that address rather than the
      // pieces of the label. Keep in step with Formatter::getDiscussionLinkTemplate().
      clone.find('a.UrlLink--discussion').replaceWith(function () {
        // A text node, so that the address is not parsed as HTML.
        return document.createTextNode(this.getAttribute('href'));
      });

      // The replacements below are text nodes: a string returned to
      // replaceWith() is parsed as HTML, and the post's text must stay text.

      // Replace emoji images with their shortcode (found in alt attribute)
      clone.find('img.emoji').replaceWith(function () {
        return document.createTextNode(this.alt);
      });

      // Mention icons decorate the name; they are not images in the post.
      clone.find('img.IconImage').remove();

      // Replace all other images with a Markdown image
      clone.find('img').replaceWith(function () {
        return document.createTextNode(`![](${this.src})`);
      });

      // Spoilers are written back so that the quote keeps them hidden. This
      // runs after the conversions above so that images and emoji in them are
      // kept. A revealed inline spoiler has lost its class, so it is found by
      // data-spoiler (see the markdown extension's template).
      // Spoilers inside links go first, so the link text keeps them.
      clone.find(INLINE_SPOILER_IN_LINK).replaceWith(function () {
        keepOpening(this, clone[0]);

        return document.createTextNode(inlineSpoiler(this.textContent));
      });

      // Replace all links with a Markdown link
      clone.find('a').replaceWith(function () {
        return document.createTextNode(`[${this.innerText}](${this.href})`);
      });

      clone.find(INLINE_SPOILER).replaceWith(function () {
        keepOpening(this, clone[0]);

        return document.createTextNode(inlineSpoiler(this.textContent));
      });
      clone.find(BLOCK_SPOILER).replaceWith(function () {
        return document.createTextNode(blockSpoiler(this.textContent));
      });

      if (around) {
        return aroundInline ? inlineSpoiler(clone.text()) : blockSpoiler(clone.text());
      }

      return clone.text();
    }
  }
  return '';
}
