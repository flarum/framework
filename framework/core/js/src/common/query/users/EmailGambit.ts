import IGambit from '../IGambit';

export default class EmailGambit implements IGambit {
  pattern(): string {
    return 'email:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'email';

    return {
      [key]: matches[1],
    };
  }
}
