import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Input from '../../../../src/common/components/Input';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('Input displays as expected', () => {
  it('renders', () => {
    const input = mq(
      m(Input, {
        placeholder: 'Lorem',
        value: 'Ipsum',
        prefixIcon: 'fas fa-user',
        ariaLabel: 'Dolor',
      })
    );

    expect(input).toHaveElement('input');
    expect(input).toContainRaw('Ipsum');
    expect(input).toHaveElementAttr('input', 'aria-label', 'Dolor');
    expect(input).toHaveElementAttr('input', 'placeholder', 'Lorem');
    expect(input).toHaveElement('.fas.fa-user');
  });

  it('can be cleared', () => {
    const onchange = jest.fn();
    const input = mq(Input, {
      clearable: true,
      clearLabel: 'Clear',
      onchange,
      value: 'Ipsum',
    });
    expect(input).toHaveElementAttr('.Input-clear', 'aria-label', 'Clear');
    input.click('.Input-clear');
    expect(onchange).toHaveBeenCalledWith('');
  });

  it('can be loading', () => {
    const input = mq(Input, {
      loading: true,
    });
    expect(input).toHaveElement('.LoadingIndicator');
  });

  it('can be disabled', () => {
    const input = mq(Input, {
      disabled: true,
    });
    expect(input).toHaveElementAttr('input', 'disabled');
  });
});
