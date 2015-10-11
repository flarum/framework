import { extend } from 'flarum/extend';
import Discussion from 'flarum/models/Discussion';
import Badge from 'flarum/components/Badge';

export default function addSubscriptionBadge() {
  extend(Discussion.prototype, 'badges', function(badges) {
    let badge;

    switch (this.subscription()) {
      case 'follow':
        badge = Badge.component({
          label: app.trans('flarum-subscriptions.forum.following'),
          icon: 'star',
          type: 'following'
        });
        break;

      case 'ignore':
        badge = Badge.component({
          label: app.trans('flarum-subscriptions.forum.ignoring'),
          icon: 'eye-slash',
          type: 'ignoring'
        });
        break;

      default:
        // no default
    }

    if (badge) {
      badges.add('subscription', badge);
    }
  });
}
