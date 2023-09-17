import IGambit from '../IGambit';

export default class CreatedGambit implements IGambit {
  pattern(): string {
    return 'created:(\\d{4}\\-\\d\\d\\-\\d\\d)(?:\\.\\.(\\d{4}\\-\\d\\d\\-\\d\\d))?';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'created';

    return {
      [key]: matches[2]
        ? {
            from: matches[1],
            to: matches[2],
          }
        : matches[1],
    };
  }
}
