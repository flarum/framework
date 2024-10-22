import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Select from '../../../../src/common/components/Select';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('Select displays as expected', () => {
  it('works as expected', () => {
    const onchange = jest.fn();
    const select = mq(Select, {
      options: {
        a: 'Option A',
        b: 'Option B',
        c: {
          label: 'Option C',
          disabled: true,
        },
      },
      value: null,
      wrapperAttrs: { 'data-test': 'test' },
      className: 'select',
      onchange,
    });

    expect(select).toHaveElementAttr('.Select', 'data-test', 'test');
    expect(select).toContainRaw('Option A');
    expect(select).toContainRaw('Option B');
    expect(select).toContainRaw('Option C');

    select.setValue('select', 'a');
    expect(onchange).toHaveBeenCalledTimes(1);
    expect(onchange).toHaveBeenCalledWith('a');

    select.setValue('select', 'b');
    expect(onchange).toHaveBeenCalledTimes(2);
    expect(onchange).toHaveBeenCalledWith('b');
  });
});
