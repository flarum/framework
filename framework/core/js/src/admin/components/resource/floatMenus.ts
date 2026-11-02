/**
 * Lets the dropdown menus inside a scrolling container escape its clipping. A container that
 * scrolls sideways (the resource table's) also clips vertically, which would cut off a row menu below a
 * short table. An open menu is positioned `fixed` at its toggle instead, and kept there on scroll
 * and resize. Menus that are already fixed (core's phone sheets) are left alone.
 *
 * Returns a function that removes the observer and listeners.
 */
export default function floatMenus(container: HTMLElement): () => void {
  const properties = ['position', 'margin', 'top', 'bottom', 'left', 'right'];
  let open: { dropdown: HTMLElement; toggle: HTMLElement; menu: HTMLElement } | null = null;

  const place = () => {
    if (!open) return;

    const { toggle, menu } = open;
    const rect = toggle.getBoundingClientRect();
    const width = menu.offsetWidth;
    const height = menu.offsetHeight;

    // Core's Dropdown has already chosen up or down and left or right for the viewport.
    const up = menu.classList.contains('Dropdown-menu--top') && rect.top >= height;
    const right = menu.classList.contains('Dropdown-menu--right') || rect.left + width > window.innerWidth;

    Object.assign(menu.style, {
      position: 'fixed',
      margin: '0',
      top: `${up ? rect.top - height : rect.bottom}px`,
      bottom: 'auto',
      left: `${right ? Math.max(0, rect.right - width) : rect.left}px`,
      right: 'auto',
    });
  };

  const reset = () => {
    if (open) properties.forEach((property) => open!.menu.style.removeProperty(property));
    open = null;
  };

  const show = (dropdown: HTMLElement) => {
    const menu = dropdown.querySelector<HTMLElement>(':scope > .Dropdown-menu');
    const toggle = dropdown.querySelector<HTMLElement>(':scope > .Dropdown-toggle');

    reset();

    if (!menu || !toggle || getComputedStyle(menu).position === 'fixed') return;

    open = { dropdown, toggle, menu };
    place();
  };

  // A dropdown is open while it has the `open` class. The observer's callback runs after the
  // `shown.bs.dropdown` handlers, so core's Dropdown has already picked the menu's direction.
  const isOpen = (className: string | null) => /(^|\s)open(\s|$)/.test(className ?? '');

  const observer = new MutationObserver((records) => {
    records.forEach((record) => {
      const dropdown = record.target as HTMLElement;

      if (!dropdown.classList.contains('Dropdown')) return;

      const was = isOpen(record.oldValue);
      const is = dropdown.classList.contains('open');

      if (!was && is) show(dropdown);
      else if (was && !is && open?.dropdown === dropdown) reset();
    });
  });

  // A container outside the page's DOM (a test renderer's, such as mithril-query's domino) can't be observed.
  if (container instanceof Node) {
    observer.observe(container, { subtree: true, attributes: true, attributeFilter: ['class'], attributeOldValue: true });
  }

  window.addEventListener('scroll', place, true);
  window.addEventListener('resize', place);

  return () => {
    reset();
    observer.disconnect();
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
  };
}
