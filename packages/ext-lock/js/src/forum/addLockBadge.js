import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import Badge from 'flarum/common/components/Badge';

export default function addLockBadge() {
  extend(Discussion.prototype, 'badges', function (badges) {
    if (this.isLocked()) {
      badges.add(
        'locked',
        Badge.component({
          type: 'locked',
          label: app.translator.trans('flarum-lock.forum.badge.locked_tooltip'),
          icon: 'fas fa-lock',
        })
      );
    }
  });
}
