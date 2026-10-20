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

    // Map the matched keyword, translated or English, to the value stored in
    // the database, so SubscriptionFilter always receives 'follow' or 'ignore'.
    const keyword = matches[1].toLowerCase();
    const followKeys = [this.key()[0], this.canonicalKey()[0]].map((key) => key.toLowerCase());

    return {
      [filterKey]: followKeys.includes(keyword) ? 'follow' : 'ignore',
    };
  }

  filterKey(): string {
    return 'subscription';
  }

  fromFilter(value: string, negate: boolean): string {
    // The value is 'follow' or 'ignore', or a legacy 'following'/'ignoring';
    // show the keyword for the active locale in the search bar.
    const keyword = /^follow/i.test(value) ? this.key()[0] : this.key()[1];

    return `${negate ? '-' : ''}${this.groupKey()}:${keyword}`;
  }

  enabled(): boolean {
    return !!app.session.user;
  }
}
