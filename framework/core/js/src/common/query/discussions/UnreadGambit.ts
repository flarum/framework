import IGambit from '../IGambit';

export default class UnreadGambit implements IGambit {
  pattern(): string {
    return 'is:unread';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'unread';

    return {
      [key]: true,
    };
  }
}
