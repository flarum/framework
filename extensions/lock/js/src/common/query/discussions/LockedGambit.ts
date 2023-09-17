import IGambit from 'flarum/common/query/IGambit';

export default class LockedGambit implements IGambit {
  pattern(): string {
    return 'is:locked';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'locked';

    return {
      [key]: true,
    };
  }
}
