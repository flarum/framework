import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import extractText from '../../../../src/common/utils/extractText';

beforeAll(() => bootstrapForum());

describe('extractText', () => {
  it('should extract text from a virtual element', () => {
    const vdom = ['Hello, ', { tag: 'span', children: 'world' }, '!'];
    expect(extractText(vdom)).toBe('Hello, world!');
  });

  it('should extract text from a nested virtual element', () => {
    const vdom = ['Hello, ', { tag: 'span', children: ['world', '!'] }];
    // @ts-ignore
    expect(extractText(vdom)).toBe('Hello, world!');
  });

  it('should extract text from an array of strings', () => {
    const vdom = ['Hello, ', 'world', '!'];
    expect(extractText(vdom)).toBe('Hello, world!');
  });
});
