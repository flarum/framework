import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import ColorPreviewInput from '../../../../src/common/components/ColorPreviewInput';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('ColorPreviewInput displays as expected', () => {
  it('renders', () => {
    const input = mq(m(ColorPreviewInput, { value: '#000000' }));
    expect(input).toHaveElement('.FormControl');
  });

  it('handles correct values', () => {
    const onchange = jest.fn();
    const input = mq(ColorPreviewInput, { value: '#000000', onchange });

    // @ts-ignore
    input.trigger('input[type=color]', 'blur', { target: {} });
    expect(onchange).toHaveBeenCalledTimes(0);
  });

  it('handles wrong values', () => {
    const onchange = jest.fn();
    const input = mq(ColorPreviewInput, { value: '#fe', onchange });

    // @ts-ignore
    input.trigger('input[type=color]', 'blur', { target: {} });
    expect(onchange).toHaveBeenCalled();
  });
});
