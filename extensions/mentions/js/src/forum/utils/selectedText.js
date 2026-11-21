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

// The longest run of backticks in the text, so that a code span or fence
// around it can be one longer and not be closed early.
const longestBacktickRun = (text) => Math.max(0, ...(text.match(/`+/g) || []).map((run) => run.length));

// A code span around the text. A space keeps a backtick at either end of the
// code from joining the fence.
function codeSpan(text) {
  const fence = '`'.repeat(longestBacktickRun(text) + 1);
  const pad = text.startsWith('`') || text.endsWith('`') ? ' ' : '';

  return fence + pad + text + pad + fence;
}

// A fenced code block, in the language the block was highlighted as.
function codeBlock(pre) {
  const code = pre.querySelector('code');
  // The server's language class comes first. highlight.js adds "hljs" and,
  // for a block written without a language, the one it guessed, after it.
  const language = ((code && code.className.match(/^language-([\w+#.-]+)/)) || [])[1] || '';
  const text = (code || pre).textContent.replace(/\n$/, '');
  const fence = '`'.repeat(Math.max(3, longestBacktickRun(text) + 1));

  return `${fence}${language}\n${text}\n${fence}`;
}

// A quote's lines, each marked as quoted once more, so that a quote inside it
// keeps its depth. A blank line is marked too, so the quote doesn't end there.
const blockquote = (text) =>
  text
    .replace(/^\n+|\s+$/g, '')
    .split('\n')
    .map((line) => (line ? `> ${line}` : '>'))
    .join('\n');

// A block's text, with a blank line on each side where the text around it has
// none, so that the block starts and ends on lines of its own.
function asBlock(node, text) {
  const blankBefore = (sibling) => !sibling || /(^|\n)\n$/.test(sibling.textContent) || sibling.textContent === '';
  const before = node.previousSibling;
  const after = node.nextSibling;
  const lead = blankBefore(before) ? '' : /\n$/.test(before.textContent) ? '\n' : '\n\n';
  const trail = !after || /^\n\n/.test(after.textContent) || after.textContent === '' ? '' : /^\n/.test(after.textContent) ? '\n' : '\n\n';

  return lead + text + trail;
}

// A mention as it is written, so that the quote still mentions what the post
// did. The name is the one shown: the mention's own formatter looks it up again
// by its ID. Keep in step with ConfigureMentions.
function mentionSource(element) {
  const id = element.getAttribute('data-id');
  // Cleaned as getCleanDisplayName does: a name holding "#p1 would otherwise
  // end the mention early, and mention another post.
  const name = (element.querySelector('.GroupMention-name') || element).textContent
    .trim()
    .replace(/^@/, '')
    .replace(/[\r\n\u2028\u2029]+/g, ' ')
    .replace(/"#[a-z]{0,3}[0-9]+/g, '_');

  if (element.matches('.PostMention') && id) return `@"${name}"#p${id}`;
  if (element.matches('.UserMention') && id) return `@"${name}"#${id}`;
  if (element.matches('.GroupMention') && id) return `@"${name}"#g${id}`;

  if (element.matches('a.TagMention')) {
    const slug = decodeURIComponent((element.getAttribute('href') || '').split('/').pop() || '');

    if (slug) return `#${slug}`;
  }

  // Rendered before mentions carried their ID, or deleted: the name as text.
  return element.textContent.trim();
}

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

// Whether the reader could select the text: it is not hidden, nor in an
// element such as a button that text cannot be selected in. Ancestors above
// the selection's common one are shared by all of it, so they are not checked.
function selectable(text, root) {
  if (getComputedStyle(text.parentElement).visibility === 'hidden') return false;

  for (let element = text.parentElement; element; element = element.parentElement) {
    const style = getComputedStyle(element);

    if (style.display === 'none' || (style.userSelect || style.webkitUserSelect) === 'none') return false;
    if (element === root) break;
  }

  return true;
}

/**
 * Finds the part of the selection in the provided post body, or null when
 * there is none to quote.
 */
export function selectedRange(body) {
  const selection = window.getSelection();

  if (selection.isCollapsed) return null;

  const range = selection.getRangeAt(0);
  const element = body[0];

  const root = range.commonAncestorContainer;

  if (element.contains(root)) return range;

  // Chrome takes a triple click on a post's last paragraph on to the next
  // place a selection can start, in the next post or the reply placeholder,
  // over the post's controls, whose text cannot be selected. Quote the part in
  // the body when the selection starts or ends in it and takes in no other
  // text the reader could select.
  const inBody = (node) => element.contains(node);

  if (!inBody(range.startContainer) && !inBody(range.endContainer)) return null;

  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);

  if (!inBody(range.endContainer)) {
    let last = element;
    while (last.lastChild) last = last.lastChild;
    walker.currentNode = last;

    while (walker.nextNode() && range.comparePoint(walker.currentNode, 0) <= 0) {
      const text = walker.currentNode;
      const data = text === range.endContainer ? text.data.slice(0, range.endOffset) : text.data;

      if (data.trim() && selectable(text, root)) return null;
    }
  }

  if (!inBody(range.startContainer)) {
    walker.currentNode = element;

    while (walker.previousNode() && range.comparePoint(walker.currentNode, walker.currentNode.length) >= 0) {
      const text = walker.currentNode;
      const data = text === range.startContainer ? text.data.slice(range.startOffset) : text.data;

      if (data.trim() && selectable(text, root)) return null;
    }
  }

  const inside = range.cloneRange();

  if (!inBody(range.startContainer)) inside.setStart(element, 0);
  if (!inBody(range.endContainer)) inside.setEnd(element, element.childNodes.length);

  return inside.collapsed ? null : inside;
}

/**
 * Finds the selected text in the provided composer body.
 */
export default function selectedText(body) {
  const range = selectedRange(body);

  if (!range) return '';

  const parent = range.commonAncestorContainer;
  const clone = $('<div>').append(range.cloneContents());

  // A selection entirely inside a spoiler has none in its fragment: it is
  // wrapped in the spoiler it was taken from below. Only a spoiler inside
  // the post body counts.
  const element = parent.nodeType === Node.ELEMENT_NODE ? parent : parent.parentElement;
  const found = element.closest(`${BLOCK_SPOILER}, ${INLINE_SPOILER}`);
  const around = found && $.contains(body[0], found) ? found : null;
  const aroundInline = around && !around.matches(BLOCK_SPOILER);

  // Likewise for a selection entirely inside code.
  const foundCode = element.closest('pre, code');
  const aroundCode = foundCode && $.contains(body[0], foundCode) ? foundCode : null;

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

    // Backslashes are literal in code, which is read before spoilers: code
    // needs no escaping, and would show it.
    if (aroundCode || container.closest('code, pre')) continue;

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

  // Scripts and styles in the post, such as the code highlighter's loader in
  // a code block, are not text the reader sees.
  clone.find('script, style').remove();

  // Mentions are quoted as they were written, not as links to them. Before the
  // icons are removed below, since a mention's name is its text.
  clone.find('.PostMention, .UserMention, .GroupMention, .TagMention').replaceWith(function () {
    return document.createTextNode(mentionSource(this));
  });

  // Code keeps its fences, so it stays code in the quote.
  clone.find('pre').replaceWith(function () {
    return document.createTextNode(asBlock(this, codeBlock(this)));
  });
  clone.find('code').replaceWith(function () {
    return document.createTextNode(codeSpan(this.textContent));
  });

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
  // A quote's citation, as BBCode's [quote=Name] shows it, goes on a line of
  // its own above what was quoted.
  clone.find('blockquote cite').replaceWith(function () {
    return document.createTextNode(this.textContent.trim() + '\n');
  });

  // Quotes and block spoilers keep their depth: the innermost first, so that
  // each one around it marks the lines of the ones inside it again.
  clone
    .find(`blockquote, ${BLOCK_SPOILER}`)
    .toArray()
    .reverse()
    .forEach((block) => {
      const text = block.matches('blockquote') ? blockquote(block.textContent) : blockSpoiler(block.textContent);

      block.replaceWith(document.createTextNode(asBlock(block, text)));
    });

  let text = clone.text();

  // A selection entirely inside code has none of the code element in its
  // fragment: it is wrapped in the kind of code it was taken from, and then in
  // the spoiler around that.
  if (aroundCode) {
    const pre = aroundCode.closest('pre');

    if (pre) {
      const block = pre.cloneNode(false);
      const code = pre.querySelector('code');

      block.appendChild(code ? Object.assign(code.cloneNode(false), { textContent: text }) : document.createTextNode(text));
      text = codeBlock(block);
    } else {
      text = codeSpan(text);
    }
  }

  if (around) return aroundInline ? inlineSpoiler(text) : blockSpoiler(text);

  return text;
}
