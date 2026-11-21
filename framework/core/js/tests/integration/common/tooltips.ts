import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';

/**
 * The test environment stubs `$.fn.tooltip`. Tests that check the text a
 * tooltip shows, rather than just the attributes on its trigger, need
 * Bootstrap's own plugin, with the transition plugin the forum loads.
 *
 * Call from `beforeAll`, and remove the tips it appends after each test with
 * `removeTooltips`.
 */
export async function useRealBootstrapTooltips() {
  bootstrapForum();

  (global as any).jQuery = (window as any).jQuery = $;

  // jsdom has no CSS transitions, so make Bootstrap's feature detection find
  // them; it runs on DOM ready, after the import.
  Object.defineProperty((window as any).CSSStyleDeclaration.prototype, 'transition', { get: () => '', configurable: true });
  await import('bootstrap/js/transition');
  await new Promise((resolve) => $(resolve));
  await import('bootstrap/js/tooltip');
}

/** The Bootstrap tooltip attached to an element. */
export function bsTooltip(element: Element) {
  return ($(element) as any).data('bs.tooltip');
}

/** Whether a tooltip is currently on screen. */
export function tooltipShown() {
  return $('.tooltip.in').length > 0;
}

/** Removes the tips Bootstrap appended to the document. */
export function removeTooltips() {
  $('.tooltip').remove();
}
