import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import MultiSelect from '../../../../src/common/components/MultiSelect';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('MultiSelect displays as expected', () => {
  it('works as expected', () => {
    const onchange = jest.fn();
    const select = mq(MultiSelect, {
      options: {
        a: 'Option A',
        b: 'Option B',
        c: {
          label: 'Option C',
          disabled: true,
          tooltip: 'Disabled',
        },
      },
      value: ['b'],
      wrapperAttrs: { 'data-test': 'test' },
      className: 'select',
      onchange,
    });

    expect(select).toHaveElementAttr('.Select', 'data-test', 'test');
    expect(select).toContainRaw('Option A');
    expect(select).toContainRaw('Option B');
    expect(select).toContainRaw('Option C');

    select.click('.Dropdown-item--a');
    expect(onchange).toHaveBeenCalledTimes(1);
    expect(onchange).toHaveBeenCalledWith(['b', 'a']);

    select.click('.Dropdown-item--b');
    expect(onchange).toHaveBeenCalledTimes(2);
    expect(onchange).toHaveBeenCalledWith(['a']);

    select.click('.Dropdown-item--c');
    expect(onchange).toHaveBeenCalledTimes(2);
    expect(onchange).toHaveBeenCalledWith(['a']);

    select.click('.Dropdown-item--a');
    expect(onchange).toHaveBeenCalledTimes(3);
    expect(onchange).toHaveBeenCalledWith([]);

    select.click('.Dropdown-item--b');
    expect(onchange).toHaveBeenCalledTimes(4);
    expect(onchange).toHaveBeenCalledWith(['b']);
  });
});
