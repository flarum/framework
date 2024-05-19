import * as string from '../../../../src/common/utils/string';

describe('string', () => {
  it('should slugify a string', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify)).toBe('hello-world');
  });

  it('should slugify a string with a custom slugging mode', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify, string.SluggingMode.UTF8)).toBe('hello-world');
  });

  it('should slugify a string with a custom slugging mode', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify, string.SluggingMode.ALPHANUMERIC)).toBe('hello-world');
  });

  it('should make the first character of a string uppercase', () => {
    const stringToUppercase = 'hello, world!';
    expect(string.ucfirst(stringToUppercase)).toBe('Hello, world!');
  });

  it('should transform a camel case string to snake case', () => {
    const stringToTransform = 'helloWorld';
    expect(string.camelCaseToSnakeCase(stringToTransform)).toBe('hello_world');
  });
});
