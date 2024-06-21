import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import SplitDropdown from '../../../../src/common/components/SplitDropdown';
import mq from 'mithril-query';
import m from 'mithril';

beforeAll(() => bootstrapForum());

describe('SplitDropdown displays as expected', () => {
  it('works as expected', () => {
    const buttons = [
      m('button', { className: 'button-1' }, 'Option A'),
      m('button', { className: 'button-2' }, 'Option B'),
      m('button', { className: 'button-3' }, 'Option C'),
    ];

    const select = mq(
      m(
        SplitDropdown,
        {
          label: 'Select the option',
        },
        buttons
      )
    );

    expect(select).not.toContainRaw('Select an option');
    expect(select).toContainRaw('Option A');
    expect(select).toContainRaw('Option B');
    expect(select).toContainRaw('Option C');

    // First button is displayed as its own button separate from the dropdown
    expect(select).toHaveElement('.SplitDropdown-button.button-1');
    expect(select).toHaveElement('li .button-1');
    expect(select).not.toHaveElement('.SplitDropdown-button.button-2');
    expect(select).toHaveElement('li .button-2');
    expect(select).not.toHaveElement('.SplitDropdown-button.button-3');
    expect(select).toHaveElement('li .button-3');
  });
});
