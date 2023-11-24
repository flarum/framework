import app from 'flarum/common/app';
import IGambit, { GambitType } from 'flarum/common/query/IGambit';

export default class SubscriptionGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;

  pattern(): string {
    return 'is:(follow|ignor)(?:ing|ed)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const type = matches[1] === 'follow' ? 'following' : 'ignoring';

    return {
      subscription: type,
    };
  }

  filterKey(): string {
    return 'subscription';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:${value}`;
  }

  suggestion() {
    return {
      group: 'is',
      key: [
        app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.following_key', {}, true),
        app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.ignoring_key', {}, true),
      ],
    };
  }
}
