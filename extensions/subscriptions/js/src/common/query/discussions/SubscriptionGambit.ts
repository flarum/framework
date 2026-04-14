import app from 'flarum/common/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

export default class SubscriptionGambit extends BooleanGambit {
  key(): string[] {
    return [
      app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.following_key', {}, true),
      app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.ignoring_key', {}, true),
    ];
  }

  canonicalKey(): string[] {
    return ['following', 'ignoring'];
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const filterKey = (negate ? '-' : '') + this.filterKey();

    // Map the matched surface keyword to the canonical internal DB value.
    // This ensures SubscriptionFilter always receives 'follow' or 'ignore'
    // regardless of which locale keyword was used.
    const allFollowKeys = [
      'following',
      'followed',
      app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.following_key', {}, true),
    ];

    const value = allFollowKeys.includes(matches[1]) ? 'follow' : 'ignore';

    return {
      [filterKey]: value,
    };
  }

  filterKey(): string {
    return 'subscription';
  }

  fromFilter(value: string, negate: boolean): string {
    const key = this.key();
    // value is the canonical DB value ('follow' or 'ignore'); map back to
    // the locale-appropriate surface keyword for display in the search bar.
    const keyword = value === 'follow' ? key[0] : key[1];

    return `${negate ? '-' : ''}is:${keyword}`;
  }

  enabled(): boolean {
    return !!app.session.user;
  }
}
