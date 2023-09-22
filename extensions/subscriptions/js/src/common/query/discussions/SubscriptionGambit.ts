import IGambit from 'flarum/common/query/IGambit';

export default class SubscriptionGambit implements IGambit {
  pattern(): string {
    return 'is:(follow|ignor)(?:ing|ed)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const type = matches[1] === 'follow' ? 'following' : 'ignoring';

    return {
      subscription: type,
    };
  }
}
