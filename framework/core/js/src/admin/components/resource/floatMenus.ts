/**
 * Lets the dropdown menus inside a scrolling container escape its clipping. A container that
 * scrolls sideways (the resource table's) also clips vertically, so a row menu below a short table
 * was cut off. An open menu is positioned `fixed` at its toggle instead, and kept there on scroll
 * and resize. Menus that are already fixed (core's phone sheets) are left alone.
 *
 * Returns a function that removes the listeners.
 *
 * @experimental
 */
export default function floatMenus(container: HTMLElement): () => void {
  const properties = ['position', 'margin', 'top', 'bottom', 'left', 'right'];
  let open: { toggle: HTMLElement; menu: HTMLElement } | null = null;

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

  // Core's Dropdown triggers these as jQuery events, which native listeners don't receive.
  const $container = $(container);

  $container.on('shown.bs.dropdown.floatMenus', (e) => {
    const dropdown = e.target as HTMLElement;
    const menu = dropdown.querySelector<HTMLElement>(':scope > .Dropdown-menu');
    const toggle = dropdown.querySelector<HTMLElement>(':scope > .Dropdown-toggle');

    reset();

    if (!menu || !toggle || getComputedStyle(menu).position === 'fixed') return;

    open = { toggle, menu };
    place();
  });

  $container.on('hidden.bs.dropdown.floatMenus', reset);

  window.addEventListener('scroll', place, true);
  window.addEventListener('resize', place);

  return () => {
    reset();
    $container.off('.floatMenus');
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
  };
}
