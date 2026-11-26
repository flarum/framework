import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import SelectDropdown from '../../../../src/common/components/SelectDropdown';
import LinkButton from '../../../../src/common/components/LinkButton';
import mq from 'mithril-query';
import m from 'mithril';
import type Mithril from 'mithril';
import { app } from '../../../../src/forum';

beforeAll(() => bootstrapForum());

describe('SelectDropdown displays as expected', () => {
  it('works as expected', () => {
    const buttons = [
      m('button', { className: 'button-1' }, 'Option A'),
      m('button', { className: 'button-2' }, 'Option B'),
      m('button', { className: 'button-3' }, 'Option C'),
    ];

    const select = mq(
      m(
        SelectDropdown,
        {
          label: 'Select the option',
          defaultLabel: 'Select an option',
        },
        buttons
      )
    );

    expect(select).toContainRaw('Select an option');
    expect(select).toContainRaw('Option A');
    expect(select).toContainRaw('Option B');
    expect(select).toContainRaw('Option C');
  });

  /**
   * Which option is selected is shown visually by a class on the item, and by
   * the toggle button taking that option's label. Neither reaches a screen
   * reader, so the menu read as an undifferentiated list of options with no
   * indication of which one was in effect (flarum/framework#3362).
   *
   * `aria-current` is used rather than `aria-selected`, which is only valid on
   * roles this menu does not claim (`option`, `tab`, `row`, `gridcell`,
   * `treeitem`). Giving it those roles would mean honouring the whole listbox
   * keyboard contract; `aria-current` is valid anywhere and says what is meant —
   * the current item within a set.
   */
  it('marks the selected option for assistive technology', () => {
    const buttons = [
      m('button', { className: 'button-1', active: false }, 'Option A'),
      m('button', { className: 'button-2', active: true }, 'Option B'),
      m('button', { className: 'button-3', active: false }, 'Option C'),
    ];

    const select = mq(
      m(
        SelectDropdown,
        {
          label: 'Select the option',
          defaultLabel: 'Select an option',
        },
        buttons
      )
    );

    const current = select.rootEl.querySelectorAll('[aria-current="true"]');

    expect(current).toHaveLength(1);
    expect(current[0].textContent).toContain('Option B');
  });

  /**
   * Mithril omits an attribute set to `false` entirely, so the unselected items
   * must not be given `aria-current` at all rather than `aria-current="false"`.
   */
  it('does not mark unselected options', () => {
    const buttons = [
      m('button', { className: 'button-1', active: false }, 'Option A'),
      m('button', { className: 'button-2', active: true }, 'Option B'),
    ];

    const select = mq(
      m(
        SelectDropdown,
        {
          label: 'Select the option',
          defaultLabel: 'Select an option',
        },
        buttons
      )
    );

    // Every item carrying the attribute at all is the selected one.
    expect(select.rootEl.querySelectorAll('[aria-current]')).toHaveLength(1);
  });

  /**
   * A menu with nothing selected must not claim a current item.
   */
  it('marks nothing when no option is selected', () => {
    const buttons = [m('button', { className: 'button-1' }, 'Option A'), m('button', { className: 'button-2' }, 'Option B')];

    const select = mq(
      m(
        SelectDropdown,
        {
          label: 'Select the option',
          defaultLabel: 'Select an option',
        },
        buttons
      )
    );

    expect(select.rootEl.querySelectorAll('[aria-current]')).toHaveLength(0);
  });

  it('uses active button as label', () => {
    const buttons = [
      m('button', { className: 'button-1', active: false }, 'Option A'),
      m('button', { className: 'button-2', active: true }, 'Option B'),
      m('button', { className: 'button-3', active: false }, 'Option C'),
    ];

    const select = mq(
      m(
        SelectDropdown,
        {
          label: 'Select the option',
          defaultLabel: 'Select an option',
        },
        buttons
      )
    );

    expect(select).toContainRaw('Option B');
    expect(select).not.toContainRaw('Select an option');
  });

  /**
   * The active option's label is also shown in the toggle, so it is rendered
   * twice: once in the menu, and once in the button. A vnode can only be
   * rendered in one place at a time, so the button needs its own copy of the
   * label, or it keeps showing the count the option had before the redraw.
   */
  it('keeps the active option label current in both copies', () => {
    const count = { value: 2 };

    const select = mq({
      view: () =>
        m(SelectDropdown, { label: 'Select the option', defaultLabel: 'Select an option' }, [
          // Built like the profile's Posts tab: a LinkButton whose label is a
          // translated string followed by the styled count.
          m(LinkButton, { href: '/u/alice/posts', icon: 'far fa-comment', active: true }, [
            'Posts ',
            m('span', { className: 'Button-badge' }, String(count.value)),
          ]),
        ]),
    });

    const toggleBadge = () => select.rootEl.querySelector('.Dropdown-toggle .Button-labelText .Button-badge');
    const menuBadge = () => select.rootEl.querySelector('.Dropdown-menu .Button-badge');

    expect(toggleBadge()?.textContent).toBe('2');
    expect(menuBadge()?.textContent).toBe('2');

    count.value = 1;

    select.redraw();

    // One redraw moves both copies of the count.
    expect(toggleBadge()?.textContent).toBe('1');
    expect(menuBadge()?.textContent).toBe('1');
  });

  it('keeps the badge markup in the toggle instead of flattening it to text', () => {
    const select = mq(
      m(SelectDropdown, { label: 'Select the option', defaultLabel: 'Select an option' }, [
        m(LinkButton, { href: '/u/alice/posts', active: true }, ['Posts ', m('span', { className: 'Button-badge' }, '2')]),
      ])
    );

    const badge = select.rootEl.querySelector('.Dropdown-toggle .Button-labelText .Button-badge');

    expect(badge?.tagName).toBe('SPAN');
    expect(badge?.classList.contains('Button-badge')).toBe(true);
    expect(select.rootEl.querySelector('.Dropdown-toggle .Button-labelText')?.textContent).toBe('Posts 2');
  });

  /**
   * `defaultLabel` is often a translation, which arrives as an array of strings
   * and vnodes rather than a single string. It must reach the toggle as markup.
   */
  it('keeps a formatted default label', () => {
    const select = mq(
      m(SelectDropdown, { label: 'Select the option', defaultLabel: ['Select ', m('strong', 'an option')] }, [
        m('button', { className: 'button-1' }, 'Option A'),
      ])
    );

    const label = select.rootEl.querySelector('.Dropdown-toggle .Button-labelText');

    expect(label?.querySelector('strong')?.textContent).toBe('an option');
    expect(label?.textContent).toBe('Select an option');
  });

  /**
   * An item can key a fragment on the vnode itself and key a sibling through
   * its attrs. Mithril rejects a list that mixes keyed and unkeyed siblings, so
   * the copy has to keep the fragment's key.
   */
  it('keeps the key of a fragment in the copied label', () => {
    const fragment = m.fragment({}, [m('span', { className: 'fragment-item' }, 'Fragment')]);
    fragment.key = 'fragment';

    const select = mq(
      m(SelectDropdown, { label: 'Select the option', defaultLabel: 'Select an option' }, [
        m('button', { active: true }, [fragment, m('span', { className: 'keyed-sibling', key: 'sibling' }, 'Sibling')]),
      ])
    );

    const label = select.rootEl.querySelector('.Dropdown-toggle .Button-labelText');

    expect(label?.querySelector('.fragment-item')?.textContent).toBe('Fragment');
    expect(label?.querySelector('.keyed-sibling')?.textContent).toBe('Sibling');
  });

  /**
   * A custom item can take any data as a child and render it itself, so the
   * label copy has to hand that data back rather than treat it as a vnode.
   */
  it('passes plain data children of a custom item through unchanged', () => {
    const data = { label: 'Custom' };

    const CustomItem = {
      view: (vnode: Mithril.Vnode<any, any>) => m('span', { className: 'custom-label' }, (vnode.children as unknown as (typeof data)[])[0].label),
    };

    const select = mq(
      m(SelectDropdown, { label: 'Select the option', defaultLabel: 'Select an option' }, [
        m('button', { active: true }, [m(CustomItem, {}, data as any)]),
      ])
    );

    expect(select.rootEl.querySelector('.Dropdown-toggle .Button-labelText .custom-label')?.textContent).toBe('Custom');
    expect(select.rootEl.querySelector('.Dropdown-menu .custom-label')?.textContent).toBe('Custom');
  });
});

describe('SelectDropdown renders an optional icon', () => {
  const buttons = () => [m('button', { className: 'button-1', active: true }, 'Option A')];

  it('renders no icon by default', () => {
    const select = mq(m(SelectDropdown, { label: 'Select', defaultLabel: 'Select' }, buttons()));

    expect(select).not.toHaveElement('.Dropdown-toggle .Button-icon');
  });

  it('renders an icon when given one', () => {
    // Something has to identify the control wherever its label is hidden —
    // the locale selector in the header collapses to its icon alone.
    const select = mq(m(SelectDropdown, { label: 'Select', defaultLabel: 'Select', icon: 'fas fa-globe' }, buttons()));

    expect(select).toHaveElement('.Dropdown-toggle .Button-icon');
    expect(select).toHaveElement('.fa-globe');
  });

  it('keeps showing the active option alongside the icon', () => {
    const select = mq(m(SelectDropdown, { label: 'Select', defaultLabel: 'Select', icon: 'fas fa-globe' }, buttons()));

    expect(select).toContainRaw('Option A');
  });
});

describe('SelectDropdown forwards noStyleOverride to its toggle icon', () => {
  beforeAll(() => app.boot());

  afterEach(() => app.forum.pushAttributes({ fontAwesomeForcedStyle: null }));

  it('keeps the declared toggle icon style when noStyleOverride is set', () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-duotone fa-light' });

    const select = mq(
      m(SelectDropdown, { icon: 'fa-regular fa-sun', noStyleOverride: true, defaultLabel: 'x' }, [m('button', { active: true }, 'A')])
    );

    expect(select.rootEl.querySelector('.Dropdown-toggle .Button-icon')?.className).toContain('fa-regular');
  });

  it('rewrites the toggle icon style by default when the forum forces one', () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-duotone fa-light' });

    const select = mq(m(SelectDropdown, { icon: 'fa-regular fa-sun', defaultLabel: 'x' }, [m('button', { active: true }, 'A')]));

    expect(select.rootEl.querySelector('.Dropdown-toggle .Button-icon')?.className).not.toContain('fa-regular');
  });
});
