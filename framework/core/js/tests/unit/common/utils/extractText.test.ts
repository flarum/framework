import extractText from '../../../../src/common/utils/extractText';

describe('extractText', () => {
  it('should extract text from a virtual element', () => {
    const vdom = ['Hello, ', { tag: 'span', children: 'world' }, '!'];
    // @ts-ignore
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

  it('should ignore absent children at the top level', () => {
    expect(extractText(null)).toBe('');
    expect(extractText(undefined)).toBe('');
    expect(extractText(false)).toBe('');
    expect(extractText(true)).toBe('');
  });

  it('should ignore absent children inside an array', () => {
    expect(extractText(['Tags', null, undefined, false, true])).toBe('Tags');
  });

  it('should ignore absent children nested in virtual elements', () => {
    const vdom = ['Tags', { tag: 'span', children: ['!', false, null, [undefined, true, '?']] }];
    // @ts-ignore
    expect(extractText(vdom)).toBe('Tags!?');
  });

  it('should ignore absent text and children on a virtual element', () => {
    // @ts-ignore
    expect(extractText({ tag: 'span', children: null, text: undefined })).toBe('');
    // @ts-ignore
    expect(extractText({ tag: 'span', children: false })).toBe('');
  });

  it('should still stringify numbers, including zero', () => {
    expect(extractText(0)).toBe('0');
    expect(extractText(['Count: ', 0, ' of ', 3])).toBe('Count: 0 of 3');
  });
});
