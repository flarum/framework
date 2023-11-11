import IGambit from '../IGambit';

export default class GroupGambit implements IGambit {
  pattern(): string {
    return 'group:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'group';

    return {
      [key]: matches[1].split(','),
    };
  }

  filterKey(): string {
    return 'group';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}group:${value}`;
  }
}
