import { radioAttrs, radioKeyTarget } from '../../../src/admin/radioGroup';

describe('the arrow keys move through a radio group', () => {
  test('right and down move on, wrapping at the end', () => {
    expect(radioKeyTarget('ArrowRight', 0, 4)).toBe(1);
    expect(radioKeyTarget('ArrowDown', 3, 4)).toBe(0);
  });

  test('left and up move back, wrapping at the start', () => {
    expect(radioKeyTarget('ArrowLeft', 2, 4)).toBe(1);
    expect(radioKeyTarget('ArrowUp', 0, 4)).toBe(3);
  });

  test('Home and End go to either end', () => {
    expect(radioKeyTarget('Home', 2, 4)).toBe(0);
    expect(radioKeyTarget('End', 0, 4)).toBe(3);
  });

  test('every other key is not the group’s', () => {
    ['Tab', ' ', 'Enter', 'a', 'Escape'].forEach((key) => expect(radioKeyTarget(key, 1, 4)).toBeNull());
    expect(radioKeyTarget('ArrowRight', 0, 0)).toBeNull();
  });
});

describe('only the checked radio is a Tab stop', () => {
  test('the checked one, or the first when none is', () => {
    expect([0, 1, 2].map((i) => radioAttrs(i, 1, 3).tabindex)).toEqual([-1, 0, -1]);
    expect([0, 1, 2].map((i) => radioAttrs(i, -1, 3).tabindex)).toEqual([0, -1, -1]);
    expect(radioAttrs(1, 1, 3)['aria-checked']).toBe('true');
    expect(radioAttrs(0, 1, 3)['aria-checked']).toBe('false');
  });
});

describe('the keydown handler', () => {
  function group() {
    const root = document.createElement('div');
    root.setAttribute('role', 'radiogroup');
    root.innerHTML = '<button role="radio"></button><button role="radio"></button><button role="radio"></button>';
    document.body.appendChild(root);

    return Array.from(root.querySelectorAll('button'));
  }

  function press(target: HTMLElement, key: string, handler: (e: KeyboardEvent) => void) {
    const e = new KeyboardEvent('keydown', { key, cancelable: true, bubbles: true });
    target.addEventListener('keydown', handler as EventListener, { once: true });
    target.dispatchEvent(e);

    return e;
  }

  /*
   * The trap this guards against: a handler that returns false for keys it does
   * not handle makes Mithril cancel them, and nothing could be typed or tabbed.
   */
  test('leaves every other key alone, and returns nothing Mithril would read as "cancel"', () => {
    const [first] = group();
    const attrs = radioAttrs(0, 0, 3, () => {});

    ['Tab', ' ', 'Enter', 'a'].forEach((key) => {
      const e = press(first, key, (ev) => expect(attrs.onkeydown(ev)).toBeUndefined());
      expect(e.defaultPrevented).toBe(false);
    });
  });

  test('moves focus and selects with the arrows', () => {
    const buttons = group();
    const picked: number[] = [];
    const attrs = radioAttrs(0, 0, 3, (to) => picked.push(to));

    const e = press(buttons[0], 'ArrowRight', attrs.onkeydown);

    expect(e.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(buttons[1]);
    expect(picked).toEqual([1]);
  });

  test('only moves focus when the group saves on choosing', () => {
    const buttons = group();
    const attrs = radioAttrs(2, 2, 3);

    press(buttons[2], 'Home', attrs.onkeydown);

    expect(document.activeElement).toBe(buttons[0]);
  });
});
