/**
 * Label links to discussions in the composer preview.
 *
 * The server does this while rendering a post, but the preview never reaches
 * the server: it is produced in the browser by the JavaScript TextFormatter,
 * working from the raw text alone. Without this, a link the writer has just
 * pasted looks like a bare address while they are typing and then changes into
 * a label once the post is saved.
 *
 * This mirrors the decision made in `Formatter::configureDefaultsOnLinks()`.
 * Loaded titles are available immediately; unloaded discussions keep their numeric label.
 */
export default function labelDiscussionLinks(element: HTMLElement): void;
