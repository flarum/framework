/**
 * Wire up iframe-resizer's content-window side so the parent page can size the
 * iframe to fit the discussion, and reposition modals/composer relative to the
 * parent's scroll position.
 *
 * This is verified in a real iframe via the browser harness — it can't be
 * exercised in jsdom (no parent frame, no layout).
 */
export default function setupIframeResizer(): void;
