import highlight from '../../../../src/common/helpers/highlight';

describe('highlight', () => {
  it('should return the string if no phrase or length is given', () => {
    const string = 'Hello, world!';
    expect(highlight(string)).toBe(string);
  });

  it('should highlight a phrase in a string', () => {
    const string = 'Hello, world!';
    const phrase = 'world';

    // @ts-ignore
    expect(highlight(string, phrase).children).toBe('Hello, <mark>world</mark>!');
  });

  it('should highlight a phrase in a string case-insensitively', () => {
    const string = 'Hello, world!';
    const phrase = 'WORLD';

    // @ts-ignore
    expect(highlight(string, phrase).children).toBe('Hello, <mark>world</mark>!');
  });
});
