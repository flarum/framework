import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
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

  it('preserves an empty value instead of coercing it to a colour', () => {
    // Clearing the field is a valid "no colour" state and must be saved as an
    // empty string, not turned into #000000 on blur.
    const onchange = jest.fn();
    const input = mq(ColorPreviewInput, { value: '', onchange });

    // @ts-ignore
    input.trigger('input[type=color]', 'blur', { target: {} });

    expect(onchange).not.toHaveBeenCalledWith({ target: { value: '#000000' } });
  });

  it('keeps a deliberately-chosen black (#000000)', () => {
    // The empty-value exemption must not stop a user setting black on purpose:
    // #000000 is a valid colour and blur must leave it untouched.
    const onchange = jest.fn();
    const input = mq(ColorPreviewInput, { value: '#000000', onchange });

    // @ts-ignore
    input.trigger('input[type=color]', 'blur', { target: {} });

    expect(onchange).not.toHaveBeenCalled();
  });
});
