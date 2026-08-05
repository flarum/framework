import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import SelectDropdown from '../../../../src/common/components/SelectDropdown';
import mq from 'mithril-query';
import m from 'mithril';

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
});
