import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Checkbox from '../../../../src/common/components/Checkbox';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('Checkbox displays as expected', () => {
  it('renders checkbox with text', () => {
    const checkbox = mq(
      m(
        Checkbox,
        {
          state: true,
          onchange: jest.fn(),
        },
        'Toggle This For Me'
      )
    );

    expect(checkbox).toHaveElement('label.Checkbox.on');
    expect(checkbox).toContainRaw('Toggle This For Me');
  });

  it('can be toggled', () => {
    const onchange = jest.fn();
    const checkbox = mq(Checkbox, { onchange, state: true });
    // @ts-ignore
    checkbox.trigger('input', 'change', { target: new EventTarget() });
    expect(onchange).toHaveBeenCalled();
  });
});
