import Tooltip, { type TooltipAttrs } from '../../../../src/common/components/Tooltip';
import m from 'mithril';
import { jest } from '@jest/globals';
import { bsTooltip, removeTooltips, useRealBootstrapTooltips } from '../tooltips';

beforeAll(useRealBootstrapTooltips);

let root: HTMLElement;

afterEach(() => {
  m.mount(root, null);
  root.remove();
  (document.activeElement as HTMLElement | null)?.blur();
  removeTooltips();
  jest.restoreAllMocks();
});

function mount(attrs: Partial<TooltipAttrs> | (() => Partial<TooltipAttrs>)): HTMLButtonElement {
  root = document.createElement('div');
  document.body.appendChild(root);
  const getAttrs = typeof attrs === 'function' ? attrs : () => attrs;
  m.mount(root, { view: () => m(Tooltip, getAttrs() as TooltipAttrs, m('button', 'Hi')) });

  return root.querySelector('button')!;
}

function shown(): boolean {
  return $('.tooltip.in').length > 0;
}

describe('Tooltip', () => {
  it('keeps the tooltip text and accessible name independent', () => {
    const attrs = { text: 'Notifications', ariaLabel: 'View notifications' };
    const button = mount(attrs);

    expect(button.getAttribute('aria-label')).toBe('View notifications');
    expect(bsTooltip(button).getTitle()).toBe('Notifications');

    attrs.ariaLabel = 'View unread notifications';
    m.redraw.sync();

    expect(button.getAttribute('aria-label')).toBe('View unread notifications');
    expect(bsTooltip(button).getTitle()).toBe('Notifications');
  });

  it('shows on hover once tooltipVisible changes from a boolean to undefined, on the same element', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: false };
    const button = mount(attrs);
    expect(bsTooltip(button).options.trigger).toBe('manual');

    attrs.tooltipVisible = undefined;
    m.redraw.sync();

    expect(root.querySelector('button')).toBe(button);
    expect(bsTooltip(button).options.trigger).toBe('hover focus');

    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);
  });

  it('returns to manual control when tooltipVisible changes from undefined to a boolean', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);
    expect(bsTooltip(button).options.trigger).toBe('hover focus');

    attrs.tooltipVisible = true;
    m.redraw.sync();

    expect(bsTooltip(button).options.trigger).toBe('manual');
    expect(shown()).toBe(true);

    attrs.tooltipVisible = false;
    m.redraw.sync();

    expect(shown()).toBe(false);
  });

  it('applies a changed showOnFocus', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);

    attrs.showOnFocus = false;
    m.redraw.sync();

    expect(bsTooltip(button).options.trigger).toBe('hover');
  });

  it('hides when the control is activated, so a tap does not leave it up', () => {
    const attrs = { text: 'Hello' };
    const button = mount(attrs);

    // A tap reaches the control as a hover, a focus and then a click, so the
    // tooltip is already showing by the time the button is activated.
    button.dispatchEvent(new Event('touchstart', { bubbles: true }));
    $(button).trigger('mouseenter');
    button.focus();
    expect(shown()).toBe(true);

    button.dispatchEvent(new MouseEvent('click', { bubbles: true, detail: 1 }));

    expect(shown()).toBe(false);
  });

  it('leaves the tooltip up when the control is activated from the keyboard', () => {
    const attrs = { text: 'Hello' };
    const button = mount(attrs);

    button.focus();
    expect(shown()).toBe(true);

    // Enter and Space produce a click with no pointer detail. Bootstrap does
    // not fire `focusin` again while the element keeps focus, so hiding here
    // would take the tooltip away from the keyboard user for good.
    button.dispatchEvent(new MouseEvent('click', { bubbles: true, detail: 0 }));
    expect(shown()).toBe(true);

    // It still behaves like a hover/focus tooltip afterwards.
    button.blur();
    expect(shown()).toBe(false);
    button.focus();
    expect(shown()).toBe(true);
  });

  it('hides a tooltip when a tap turns into a scroll', () => {
    const button = mount({ text: 'Hello' });

    button.dispatchEvent(new Event('touchstart', { bubbles: true }));
    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    button.dispatchEvent(new Event('touchmove', { bubbles: true }));

    expect(shown()).toBe(false);
  });

  it('hides a tooltip when the browser takes over a touch gesture', () => {
    const button = mount({ text: 'Hello' });

    button.dispatchEvent(new Event('touchstart', { bubbles: true }));
    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    button.dispatchEvent(new Event('pointercancel', { bubbles: true }));

    expect(shown()).toBe(false);
  });

  it('hides a tooltip on a touch long press', () => {
    const button = mount({ text: 'Hello' });

    button.dispatchEvent(new Event('touchstart', { bubbles: true }));
    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    button.dispatchEvent(new Event('contextmenu', { bubbles: true }));

    expect(shown()).toBe(false);
  });

  it('keeps a manually shown tooltip shown when it is rebuilt for a new position', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    const button = mount(attrs);
    expect(shown()).toBe(true);

    attrs.position = 'bottom';
    m.redraw.sync();

    expect(bsTooltip(button).options.placement).toBe('bottom');
    expect(shown()).toBe(true);
  });

  it('shows no tooltip when the text becomes empty', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);

    attrs.text = '';
    m.redraw.sync();

    expect(bsTooltip(button).getTitle()).toBeFalsy();
    expect(button.hasAttribute('aria-label')).toBe(false);

    $(button).trigger('mouseenter');
    expect(shown()).toBe(false);

    attrs.text = 'Back again';
    m.redraw.sync();

    expect(bsTooltip(button).getTitle()).toBe('Back again');
    expect(button.getAttribute('aria-label')).toBe('Back again');
  });

  it('hides a manually shown tooltip when the text becomes empty, and shows it when text returns', async () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    mount(attrs);
    expect(shown()).toBe(true);

    attrs.text = '';
    m.redraw.sync();

    expect(shown()).toBe(false);

    attrs.text = 'Back again';
    m.redraw.sync();

    expect(shown()).toBe(true);
    expect($('.tooltip.in .tooltip-inner').text()).toBe('Back again');

    // Past the fade, so a pending hide would have detached it by now.
    await new Promise((resolve) => setTimeout(resolve, 200));
    expect(shown()).toBe(true);
  });

  it('shows a manually visible tooltip once it gets text', () => {
    const attrs: Partial<TooltipAttrs> = { text: '', tooltipVisible: true };
    mount(attrs);
    expect(shown()).toBe(false);

    attrs.text = 'Hello';
    m.redraw.sync();

    expect(shown()).toBe(true);
  });

  it('keeps a hovered or focused tooltip shown when it is rebuilt for a new position', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);

    button.focus();
    expect(shown()).toBe(true);

    attrs.position = 'bottom';
    m.redraw.sync();

    expect(bsTooltip(button).options.placement).toBe('bottom');
    expect(shown()).toBe(true);

    button.blur();
    expect(shown()).toBe(false);
  });

  it('does not show a hovered tooltip early when it is rebuilt during its show delay', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', delay: 1000 };
    const button = mount(attrs);

    button.focus();
    expect(shown()).toBe(false);

    attrs.position = 'bottom';
    m.redraw.sync();

    expect(shown()).toBe(false);
  });

  it('does not show again a tooltip that other code hid', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);

    button.focus();
    bsTooltip(button).hide();
    expect(shown()).toBe(false);

    attrs.position = 'bottom';
    m.redraw.sync();

    expect(shown()).toBe(false);
  });

  it('hides a tooltip appended to the body when the page scrolls', () => {
    const button = mount({ text: 'Hello', container: 'body' });

    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    // Bootstrap positions the tip against the page when it opens, so on a
    // fixed header it would otherwise stay behind as the page scrolls.
    window.dispatchEvent(new Event('wheel'));
    window.dispatchEvent(new Event('scroll'));

    expect(shown()).toBe(false);
  });

  it('leaves a tooltip beside its control alone when the page scrolls', () => {
    const button = mount({ text: 'Hello' });

    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    window.dispatchEvent(new Event('wheel'));
    window.dispatchEvent(new Event('scroll'));

    expect(shown()).toBe(true);
  });

  it('keeps a container tooltip up when focus scrolls its control into view', () => {
    const button = mount({ text: 'Hello', container: 'body' });

    // A focus that scrolls the page is not the page moving under the pointer.
    button.focus();
    window.dispatchEvent(new Event('scroll'));

    expect(shown()).toBe(true);
  });

  it('watches for scrolling only while a container tooltip is showing', async () => {
    const add = jest.spyOn(window, 'addEventListener');
    const remove = jest.spyOn(window, 'removeEventListener');
    const scrollAdds = () => add.mock.calls.filter(([type]) => type === 'scroll').length;
    const scrollRemoves = () => remove.mock.calls.filter(([type]) => type === 'scroll').length;

    const button = mount({ text: 'Hello', container: 'body' });

    expect(scrollAdds()).toBe(0);

    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);
    expect(scrollAdds()).toBe(1);

    $(button).trigger('mouseleave');

    // The listener goes away once the tip has finished fading out.
    await new Promise((resolve) => setTimeout(resolve, 200));

    expect(scrollRemoves()).toBe(1);
  });

  it('does not watch for scrolling for a tooltip beside its control', () => {
    const add = jest.spyOn(window, 'addEventListener');
    const button = mount({ text: 'Hello' });

    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);

    expect(add.mock.calls.filter(([type]) => type === 'scroll')).toHaveLength(0);
  });

  it('applies changed html and container options', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello' };
    const button = mount(attrs);

    attrs.html = true;
    attrs.container = 'body';
    m.redraw.sync();

    expect(bsTooltip(button).options.html).toBe(true);
    expect(bsTooltip(button).options.container).toBe('body');
  });

  it('shows new text when the text and an option change in the same redraw', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    mount(attrs);

    attrs.text = 'Goodbye';
    attrs.position = 'bottom';
    m.redraw.sync();

    expect($('.tooltip.in .tooltip-inner').text()).toBe('Goodbye');
  });

  it('keeps a manually shown tooltip shown when it switches to hover/focus while focused', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    const button = mount(attrs);
    button.focus();
    expect(shown()).toBe(true);

    attrs.tooltipVisible = undefined;
    m.redraw.sync();

    expect(shown()).toBe(true);

    button.blur();
    expect(shown()).toBe(false);
  });

  it('hides a manually shown tooltip when it switches to hover/focus while not hovered or focused', () => {
    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    mount(attrs);
    expect(shown()).toBe(true);

    attrs.tooltipVisible = undefined;
    m.redraw.sync();

    expect(shown()).toBe(false);
  });

  it('does not throw when rebuilt while fading in', async () => {
    const errors: string[] = [];
    const onError = (e: ErrorEvent) => errors.push(String(e.message));
    window.addEventListener('error', onError);

    const attrs: Partial<TooltipAttrs> = { text: 'Hello', tooltipVisible: true };
    mount(attrs);
    expect($('.tooltip').hasClass('fade')).toBe(true);

    attrs.tooltipVisible = undefined;
    m.redraw.sync();

    await new Promise((resolve) => setTimeout(resolve, 200));
    window.removeEventListener('error', onError);

    expect(errors).toEqual([]);
  });

  it('is not rebuilt on every redraw when given an object delay', () => {
    const button = mount(() => ({ text: 'Hello', delay: { show: 0, hide: 0 } as any }));
    const tooltip = bsTooltip(button);

    $(button).trigger('mouseenter');
    m.redraw.sync();

    expect(bsTooltip(button) === tooltip).toBe(true);
    expect(shown()).toBe(true);
  });
});
