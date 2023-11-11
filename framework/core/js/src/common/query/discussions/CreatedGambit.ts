import IGambit from '../IGambit';

export default class CreatedGambit implements IGambit {
  pattern(): string {
    return 'created:(\\d{4}\\-\\d\\d\\-\\d\\d(?:\\.\\.(\\d{4}\\-\\d\\d\\-\\d\\d))?)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'created';

    return {
      [key]: matches[1],
    };
  }

  filterKey(): string {
    return 'created';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}created:${value}`;
  }
}
