import IGambit from 'flarum/common/query/IGambit';

export default class StickyGambit implements IGambit {
  pattern(): string {
    return 'is:sticky';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'sticky';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'sticky';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:sticky`;
  }
}
