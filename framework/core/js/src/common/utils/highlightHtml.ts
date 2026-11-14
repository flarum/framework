/**
 * Find the ranges of `text` that `regexp` matches, as `[start, end]` pairs.
 *
 * Every match is returned when `regexp` has the global flag, otherwise only
 * the first one. Empty matches are left out, as there is nothing to wrap.
 */
export function matchRanges(text: string, regexp: RegExp): Array<[number, number]> {
  const global = regexp.global ? regexp : new RegExp(regexp.source, regexp.flags + 'g');
  const ranges: Array<[number, number]> = [];

  global.lastIndex = 0;

  for (const match of text.matchAll(global)) {
    if (!match[0]) continue;

    ranges.push([match.index!, match.index! + match[0].length]);

    if (!regexp.global) break;
  }

  return ranges;
}

/**
 * Wrap matches of `regexp` in the text of an HTML string with `<mark>` tags.
 *
 * Only text nodes are searched, so tags, attribute names and attribute values
 * are left untouched. Text inside elements whose content isn't HTML text
 * (`<script>`, `<style>`, `<textarea>`, inline SVG and MathML, and the like) is
 * skipped, as is text in `<pre>` blocks, which syntax highlighters rebuild from
 * their plain text. Every match is wrapped when `regexp` has the global flag, otherwise
 * only the first one. When nothing matches, the HTML is returned exactly as it
 * was given.
 *
 * The HTML is parsed into an inert `<template>`, so scripts don't run and
 * images don't load while it's being processed.
 */
export default function highlightHtml(html: string, regexp: RegExp): string {
  const template = document.createElement('template');
  template.innerHTML = html;

  const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_TEXT, {
    acceptNode: (node) => (node.parentElement?.closest(SKIPPED_ELEMENTS) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT),
  });

  // Collect the nodes first: replacing them while walking would derail the walker.
  const textNodes: Text[] = [];
  while (walker.nextNode()) textNodes.push(walker.currentNode as Text);

  let changed = false;

  for (const node of textNodes) {
    const text = node.data;
    const ranges = matchRanges(text, regexp);

    if (!ranges.length) continue;

    const fragment = document.createDocumentFragment();
    let lastIndex = 0;

    for (const [start, end] of ranges) {
      if (start > lastIndex) fragment.append(text.slice(lastIndex, start));

      const mark = document.createElement('mark');
      mark.textContent = text.slice(start, end);
      fragment.append(mark);

      lastIndex = end;
    }

    if (lastIndex < text.length) fragment.append(text.slice(lastIndex));

    node.replaceWith(fragment);
    changed = true;

    if (!regexp.global) break;
  }

  return changed ? template.innerHTML : html;
}

const SKIPPED_ELEMENTS = 'pre, script, style, textarea, noscript, template, title, xmp, iframe, noembed, noframes, plaintext, svg, math';
