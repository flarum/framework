import { extend } from 'flarum/extend';
import app from 'flarum/app';
import UserControls from 'flarum/utils/UserControls';
import Button from 'flarum/components/Button';
import Badge from 'flarum/components/Badge';
import Model from 'flarum/Model';
import User from 'flarum/models/User';

import SuspendUserModal from 'suspend/components/SuspendUserModal';

app.initializers.add('suspend', () => {
  User.prototype.canSuspend = Model.attribute('canSuspend');
  User.prototype.suspendUntil = Model.attribute('suspendUntil', Model.transformDate);

  extend(UserControls, 'moderationControls', (items, user) => {
    if (user.canSuspend()) {
      items.add('suspend', Button.component({
        children: 'Suspend',
        icon: 'shield',
        onclick: () => app.modal.show(new SuspendUserModal({user}))
      }));
    }
  });

  extend(User.prototype, 'badges', function(items) {
    const until = this.suspendUntil();

    if (new Date() < until) {
      items.add('suspended', Badge.component({
        icon: 'times',
        type: 'suspended',
        label: 'Suspended'
      }));
    }
  });
});
