import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import Badge from 'flarum/common/components/Badge';

export default function addStickyBadge() {
  extend(Discussion.prototype, 'badges', function (badges) {
    if (this.isSticky()) {
      badges.add(
        'sticky',
        <Badge type="sticky" label={app.translator.trans('flarum-sticky.forum.badge.sticky_tooltip')} icon="fas fa-thumbtack" />,
        10
      );
    }
  });
}
