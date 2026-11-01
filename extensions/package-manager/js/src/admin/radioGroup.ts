/**
 * Keyboard support for the `role="radio"` button groups (categories on
 * Discover, the choices in Sources), following the WAI-ARIA radio group
 * pattern: one Tab stop for the whole group, on the checked radio, and the
 * arrow keys to move within it. Without it every chip was its own Tab stop
 * and the arrow keys did nothing, which is not how a radio group behaves for
 * anybody who relies on the keyboard or a screen reader.
 */

/** Where a key moves to in a group of `count` radios, or null when it is not one of the group's keys. */
export function radioKeyTarget(key: string, index: number, count: number): number | null {
  if (count < 1) return null;

  switch (key) {
    case 'ArrowRight':
    case 'ArrowDown':
      return (index + 1) % count;
    case 'ArrowLeft':
    case 'ArrowUp':
      return (index - 1 + count) % count;
    case 'Home':
      return 0;
    case 'End':
      return count - 1;
    default:
      return null;
  }
}

/**
 * The attributes for the radio at `index` of `count`, when the one at `checked`
 * is chosen (-1 for none, which leaves the first as the Tab stop).
 *
 * `select` is called with the radio the arrow keys moved to. Leave it out for
 * a group whose choice is saved to the server at once: the arrows then only
 * move focus, and Space or Return chooses, so walking past an option does not
 * save it.
 */
export function radioAttrs(index: number, checked: number, count: number, select?: (index: number) => void) {
  return {
    role: 'radio',
    'aria-checked': index === checked ? 'true' : 'false',
    tabindex: index === Math.max(checked, 0) ? 0 : -1,
    onkeydown: (e: KeyboardEvent) => {
      const target = radioKeyTarget(e.key, index, count);

      // Every other key is left alone. Returning false here would make Mithril
      // cancel it, and Tab, Space and Return would stop working.
      if (target === null) return;

      e.preventDefault();
      const group = (e.currentTarget as HTMLElement).closest('[role="radiogroup"]');
      group?.querySelectorAll<HTMLElement>('[role="radio"]')[target]?.focus();

      if (select) select(target);
    },
  };
}
