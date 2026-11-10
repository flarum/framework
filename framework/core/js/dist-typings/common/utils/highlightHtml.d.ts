/**
 * Find the ranges of `text` that `regexp` matches, as `[start, end]` pairs.
 *
 * Every match is returned when `regexp` has the global flag, otherwise only
 * the first one. Empty matches are left out, as there is nothing to wrap.
 */
export declare function matchRanges(text: string, regexp: RegExp): Array<[number, number]>;
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
export default function highlightHtml(html: string, regexp: RegExp): string;
