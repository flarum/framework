import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Tooltip, { type TooltipAttrs } from '../../../../src/common/components/Tooltip';
import m from 'mithril';

beforeAll(async () => {
  bootstrapForum();

  // The test environment stubs `$.fn.tooltip`; these tests need Bootstrap's,
  // with the transition plugin the forum loads, so tooltips fade.
  (global as any).jQuery = (window as any).jQuery = $;

  // jsdom has no CSS transitions, so make Bootstrap's feature detection find
  // them; it runs on DOM ready, after the import.
  Object.defineProperty((window as any).CSSStyleDeclaration.prototype, 'transition', { get: () => '', configurable: true });
  await import('bootstrap/js/transition');
  await new Promise((resolve) => $(resolve));
  await import('bootstrap/js/tooltip');
});

let root: HTMLElement;

afterEach(() => {
  m.mount(root, null);
  root.remove();
  (document.activeElement as HTMLElement | null)?.blur();
  $('.tooltip').remove();
});

function mount(attrs: Partial<TooltipAttrs> | (() => Partial<TooltipAttrs>)): HTMLButtonElement {
  root = document.createElement('div');
  document.body.appendChild(root);
  const getAttrs = typeof attrs === 'function' ? attrs : () => attrs;
  m.mount(root, { view: () => m(Tooltip, getAttrs() as TooltipAttrs, m('button', 'Hi')) });

  return root.querySelector('button')!;
}

function bsTooltip(button: HTMLElement) {
  return ($(button) as any).data('bs.tooltip');
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
