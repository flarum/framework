import m from 'mithril';
import Dropdown from '../../../../src/common/components/Dropdown';
import SplitDropdown from '../../../../src/common/components/SplitDropdown';
import Button from '../../../../src/common/components/Button';
import { bsTooltip, removeTooltips, useRealBootstrapTooltips } from '../tooltips';

beforeAll(useRealBootstrapTooltips);

let root: HTMLElement;

afterEach(() => {
  m.mount(root, null);
  root.remove();
  removeTooltips();
});

function mount(getVnode: () => m.Children): void {
  root = document.createElement('div');
  document.body.appendChild(root);
  m.mount(root, { view: getVnode });
}

function shown(): boolean {
  return $('.tooltip.in').length > 0;
}

describe('dropdown tooltips', () => {
  it('hides while the menu is open, and works again once it closes', () => {
    mount(() => m(Dropdown, { label: 'Sorting', tooltip: 'Change how discussions are sorted' }, [m(Button, 'Latest')]));

    const button = root.querySelector<HTMLButtonElement>('button.Dropdown-toggle')!;
    const dropdown = root.firstElementChild!;

    $(button).trigger('mouseenter');
    expect($('.tooltip.in .tooltip-inner').text()).toBe('Change how discussions are sorted');

    // Opening the menu hides the tooltip, rather than leaving it over the menu.
    $(dropdown).trigger('shown.bs.dropdown');
    m.redraw.sync();
    expect(shown()).toBe(false);

    $(dropdown).trigger('hidden.bs.dropdown');
    m.redraw.sync();

    // Closing the menu restores the hover behaviour, without a reload.
    $(button).trigger('mouseleave');
    $(button).trigger('mouseenter');
    expect(shown()).toBe(true);
  });

  it('keeps the toggle accessible name when a tooltip is added to it', () => {
    mount(() => m(Dropdown, { label: 'Sorting', tooltip: 'Change how discussions are sorted' }, [m(Button, 'Latest')]));

    const button = root.querySelector<HTMLButtonElement>('button.Dropdown-toggle')!;

    // The tooltip describes the control; it must not replace its name.
    expect(button.getAttribute('aria-label')).toBe('Sorting, Toggle dropdown menu');
    expect(bsTooltip(button).getTitle()).toBe('Change how discussions are sorted');
  });

  it('names the primary action of a split dropdown after its own label', () => {
    mount(() => m(SplitDropdown, { tooltip: 'Get an email when there are new posts' }, [m(Button, 'Follow'), m(Button, 'Ignore')]));

    const button = root.querySelector<HTMLButtonElement>('button.SplitDropdown-button')!;

    expect(button.getAttribute('aria-label')).toBe('Follow');
    expect(bsTooltip(button).getTitle()).toBe('Get an email when there are new posts');
  });

  it('uses the text of a translated aria-label on the primary action', () => {
    mount(() =>
      m(SplitDropdown, { tooltip: 'Get an email when there are new posts' }, [
        m(Button, { 'aria-label': ['Follow', ' by email'] }, 'Follow'),
        m(Button, 'Ignore'),
      ])
    );

    const button = root.querySelector<HTMLButtonElement>('button.SplitDropdown-button')!;

    // `Button` accepts translation arrays; passing one straight to
    // `setAttribute` would have joined it with a comma.
    expect(button.getAttribute('aria-label')).toBe('Follow by email');
    expect(bsTooltip(button).getTitle()).toBe('Get an email when there are new posts');
  });

  it('falls back to the label when the aria-label is an empty array', () => {
    mount(() =>
      m(SplitDropdown, { tooltip: 'Get an email when there are new posts' }, [m(Button, { 'aria-label': [] }, 'Follow'), m(Button, 'Ignore')])
    );

    const button = root.querySelector<HTMLButtonElement>('button.SplitDropdown-button')!;

    expect(button.getAttribute('aria-label')).toBe('Follow');
  });
});
