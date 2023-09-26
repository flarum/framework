import IGambit from 'flarum/common/query/IGambit';

export default class SuspendedGambit implements IGambit {
  pattern(): string {
    return 'is:suspended';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'suspended';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'suspended';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:suspended`;
  }
}
