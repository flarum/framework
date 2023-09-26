import IGambit from 'flarum/common/query/IGambit';

export default class TagGambit implements IGambit {
  pattern(): string {
    return 'tag:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'tag';

    return {
      [key]: matches[1].split(','),
    };
  }

  filterKey(): string {
    return 'tag';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}tag:${value}`;
  }
}
