import IGambit from '../IGambit';

export default class AuthorGambit implements IGambit {
  public pattern(): string {
    return 'author:(.+)';
  }

  public toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'author';

    return {
      [key]: matches[1].split(','),
    };
  }
}
