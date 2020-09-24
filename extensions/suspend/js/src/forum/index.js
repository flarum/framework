import { extend } from 'flarum/extend';
import app from 'flarum/app';
import UserControls from 'flarum/utils/UserControls';
import Button from 'flarum/components/Button';
import Badge from 'flarum/components/Badge';
import Model from 'flarum/Model';
import User from 'flarum/models/User';

import SuspendUserModal from './components/SuspendUserModal';
import UserSuspendedNotification from './components/UserSuspendedNotification';
import UserUnsuspendedNotification from './components/UserUnsuspendedNotification';

app.initializers.add('flarum-suspend', () => {
  app.notificationComponents.userSuspended = UserSuspendedNotification;
  app.notificationComponents.userUnsuspended = UserUnsuspendedNotification;

  User.prototype.canSuspend = Model.attribute('canSuspend');
  User.prototype.suspendedUntil = Model.attribute('suspendedUntil', Model.transformDate);

  extend(UserControls, 'moderationControls', (items, user) => {
    if (user.canSuspend()) {
      items.add('suspend', Button.component({
        icon: 'fas fa-ban',
        onclick: () => app.modal.show(SuspendUserModal, {user})
      }, app.translator.trans('flarum-suspend.forum.user_controls.suspend_button')));
    }
  });

  extend(User.prototype, 'badges', function(items) {
    const until = this.suspendedUntil();

    if (new Date() < until) {
      items.add('suspended', Badge.component({
        icon: 'fas fa-ban',
        type: 'suspended',
        label: app.translator.trans('flarum-suspend.forum.user_badge.suspended_tooltip')
      }));
    }
  });
});
