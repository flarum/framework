import app from 'flarum/common/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

export default class SubscriptionGambit extends BooleanGambit {
  key(): string[] {
    return [
      app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.following_key', {}, true),
      app.translator.trans('flarum-subscriptions.lib.gambits.discussions.subscription.ignoring_key', {}, true),
    ];
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + this.filterKey();

    return {
      [key]: matches[1],
    };
  }

  filterKey(): string {
    return 'subscription';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:${value}`;
  }

  enabled(): boolean {
    return !!app.session.user;
  }
}
