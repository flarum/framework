import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
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
